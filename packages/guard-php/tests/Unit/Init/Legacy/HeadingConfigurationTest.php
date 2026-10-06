<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Reader\DocumentListConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\DocumentationConfig;
use Guard\Config\Value\DocumentConfig;
use Guard\Init\Legacy\HeadingConfiguration;
use Guard\Init\Legacy\HeadingReportConfig;
use Guard\Init\Legacy\HeadingReportReader;
use Guard\Policy\PolicyException;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\HeadingConfiguration
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Config\Value\DocumentConfig
 * @uses \Guard\Config\Value\DocumentationConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Init\Legacy\HeadingReportConfig
 * @uses \Guard\Init\Legacy\HeadingReportReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 */
#[CoversClass(HeadingConfiguration::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Filesystem\Path::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(HeadingConfigStringListReader::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentationConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(HeadingReportConfig::class)]
#[UsesClass(HeadingReportReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
final class HeadingConfigurationTest extends TestCase
{
    public function testLoadParsesDocYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/docs.yaml', <<<'YAML'
documents:
  README.md:
    headings:
      - '# Tool'
      - '## Usage'
  docs/spec.md:
    headings:
      - '# Spec'
    max_level: 1
scan:
  - '*.md'
report:
  reporter: json
  order_by: [rule]
YAML);

        $config = (new HeadingConfiguration())->load($dir . '/docs.yaml');

        self::assertSame($dir, $config->root);
        self::assertSame('docs.yaml', $config->configName);
        self::assertCount(2, $config->documents);
        self::assertSame('docs/spec.md', $config->documents[1]->path);
        self::assertSame(1, $config->documents[1]->maxLevel);
        self::assertSame(['*.md'], $config->scan);
    }

    public function testLoadAppliesDefaults(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: []\n");

        $config = (new HeadingConfiguration())->load($dir . '/doc-guard.yaml');

        self::assertSame([], $config->scan);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('DocGuard config not found');

        (new HeadingConfiguration())->load(sys_get_temp_dir() . '/missing-docguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents: [\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml');

        (new HeadingConfiguration())->load($dir . '/doc-guard.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "42\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new HeadingConfiguration())->load($dir . '/doc-guard.yaml');
    }

    public function testLoadRejectsUnknownTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: []\npaths: ['.']\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"top-level" contains unsupported key "paths"');

        (new HeadingConfiguration())->load($dir . '/doc-guard.yaml');
    }
}
