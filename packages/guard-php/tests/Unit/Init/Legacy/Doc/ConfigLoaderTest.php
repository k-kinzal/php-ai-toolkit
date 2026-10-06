<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Doc;

use Guard\Collect\Markdown\Filesystem\PathResolver;
use Guard\Collect\Markdown\Parsing\AtxHeadingMatcher;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use Guard\Config\Doc\ConfigKeyValidator;
use Guard\Config\Doc\ConfigStringListReader;
use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DeclaredHeadingReader;
use Guard\Config\Doc\DocumentationConfig;
use Guard\Config\Doc\DocumentConfig;
use Guard\Config\Doc\DocumentConfigReader;
use Guard\Config\Doc\DocumentListConfigReader;
use Guard\Init\Legacy\Doc\ConfigLoader;
use Guard\Init\Legacy\Doc\ReportConfig;
use Guard\Init\Legacy\Doc\ReportConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Doc\ConfigLoader
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\ConfigStringListReader
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Doc\ReportConfig
 * @uses \Guard\Init\Legacy\Doc\ReportConfigReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigLoader::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(DocumentationConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigLoaderTest extends TestCase
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

        $config = (new ConfigLoader())->load($dir . '/docs.yaml');

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

        $config = (new ConfigLoader())->load($dir . '/doc-guard.yaml');

        self::assertSame([], $config->scan);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('DocGuard config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-docguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents: [\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml');

        (new ConfigLoader())->load($dir . '/doc-guard.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "42\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new ConfigLoader())->load($dir . '/doc-guard.yaml');
    }

    public function testLoadRejectsUnknownTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: []\npaths: ['.']\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"top-level" contains unsupported key "paths"');

        (new ConfigLoader())->load($dir . '/doc-guard.yaml');
    }
}
