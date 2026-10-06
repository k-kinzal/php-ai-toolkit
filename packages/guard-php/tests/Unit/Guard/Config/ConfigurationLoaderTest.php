<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\ConfigurationLoader
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\Guard\Config\Configuration
 * @uses \Toolkit\Guard\Config\DocumentMerger
 * @uses \Toolkit\Guard\Config\ImportResolver
 * @uses \Toolkit\Guard\Config\DocumentationReader
 * @uses \Toolkit\Guard\Config\MetricsReader
 * @uses \Toolkit\Guard\Config\RuleReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Config\StructureReader
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyDefinition
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyResolver
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\LocGuardException
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(\Toolkit\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeading::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeadingReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentListConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\DocGuard\DocGuardException::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\DocGuardPathResolver::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Heading::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Toolkit\Guard\Config\Configuration::class)]
#[UsesClass(\Toolkit\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Toolkit\Guard\Config\ImportResolver::class)]
#[UsesClass(\Toolkit\Guard\Config\DocumentationReader::class)]
#[UsesClass(\Toolkit\Guard\Config\MetricsReader::class)]
#[UsesClass(\Toolkit\Guard\Config\RuleReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Config\StructureReader::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LocGuardConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyDefinition::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyListConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyResolver::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ScanConfig::class)]
#[UsesClass(\Toolkit\LocGuard\LocGuardException::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleListConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\TreeGuardException::class)]
final class ConfigurationLoaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRejectsUnknownTopLevelKeys(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-config-');
        self::assertIsString($path);
        file_put_contents($path, "version: 1\nconfiguraiton: []\n");
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Config\ConfigurationLoader())->load($path);
    }

    /**
     * @throws JsonException
     */
    public function testLoadsAConfigurationOnlyPolicy(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-config-');
        self::assertIsString($path);
        file_put_contents($path, "version: 1\nconfiguration:\n  - id: workers\n    file: app.json\n    select: /workers\n    assert: {min: 1}\n    repair: 1\n");
        $config = (new \Toolkit\Guard\Config\ConfigurationLoader())->load($path);
        self::assertCount(1, $config->rules);
        self::assertSame(1, $config->rules[0]->repair);
    }

    /**
     * @throws JsonException
     */
    public function testLoadsImportedRulesAndProjectOverrides(): void
    {
        $root = sys_get_temp_dir() . '/guard-import-load-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "configuration:\n  - {id: mode, file: app.json, select: /mode, assert: {equals: A}}\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\nconfiguration:\n  - {id: mode, file: settings.json}\n");
        $config = (new \Toolkit\Guard\Config\ConfigurationLoader())->load($root . '/guard.yaml');
        self::assertSame('settings.json', $config->rules[0]->file);
        self::assertSame(['equals' => 'A'], $config->rules[0]->assertions);
    }

}
