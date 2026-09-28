<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\ConfigKeyValidator;
use Toolkit\DocGuard\Config\ConfigLoader;
use Toolkit\DocGuard\Config\ConfigStringListReader;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DeclaredHeadingReader;
use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\DocumentConfigReader;
use Toolkit\DocGuard\Config\DocumentListConfigReader;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Config\ReportConfigReader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;

/**
 * @covers \Toolkit\DocGuard\Config\ConfigLoader
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Config\ReportConfigReader
 */
#[CoversClass(ConfigLoader::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocGuardConfig::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
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
        self::assertSame('json', $config->report->reporter);
        self::assertSame(['rule'], $config->report->orderBy);
    }

    public function testLoadAppliesDefaults(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: []\n");

        $config = (new ConfigLoader())->load($dir . '/doc-guard.yaml');

        self::assertSame([], $config->scan);
        self::assertSame('ai', $config->report->reporter);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('DocGuard config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-docguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents: [\n");

        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml');

        (new ConfigLoader())->load($dir . '/doc-guard.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "42\n");

        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new ConfigLoader())->load($dir . '/doc-guard.yaml');
    }

    public function testLoadRejectsUnknownTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/doc-guard.yaml', "documents:\n  README.md:\n    headings: []\npaths: ['.']\n");

        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"top-level" contains unsupported key "paths"');

        (new ConfigLoader())->load($dir . '/doc-guard.yaml');
    }
}
