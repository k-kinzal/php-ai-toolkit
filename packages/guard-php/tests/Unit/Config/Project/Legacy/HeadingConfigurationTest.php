<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Project\Legacy\HeadingConfiguration;
use Guard\Config\Project\Legacy\HeadingReportConfig;
use Guard\Config\Project\Legacy\HeadingReportReader;
use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Reader\DocumentListConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DocumentationConfig;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\HeadingConfiguration
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Definition\DocumentationConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Config\Project\Legacy\HeadingReportConfig
 * @uses \Guard\Config\Project\Legacy\HeadingReportReader
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 */
#[CoversClass(HeadingConfiguration::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Path::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(HeadingConfigStringListReader::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentationConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(HeadingReportConfig::class)]
#[UsesClass(HeadingReportReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
