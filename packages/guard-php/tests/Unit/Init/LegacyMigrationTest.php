<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\LegacyMigration
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
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\LimitConfigReader
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyRuleListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyDefinition
 * @uses \Guard\Config\Loc\Policy\PolicyListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyResolver
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Config\Loc\ScanConfigReader
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Doc\ConfigLoader
 * @uses \Guard\Init\Legacy\Doc\ReportConfig
 * @uses \Guard\Init\Legacy\Doc\ReportConfigReader
 * @uses \Guard\Init\Legacy\Loc\ConfigLoader
 * @uses \Guard\Init\Legacy\Loc\ReportConfig
 * @uses \Guard\Init\Legacy\Loc\ReportConfigReader
 * @uses \Guard\Init\Legacy\Tree\ConfigLoader
 * @uses \Guard\Init\Legacy\Tree\ReportConfig
 * @uses \Guard\Init\Legacy\Tree\ReportConfigReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Init\LegacyMigration::class)]
#[UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Doc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Doc\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
#[UsesClass(\Guard\Config\Loc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Loc\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Loc\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[UsesClass(\Guard\Config\Loc\LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\MetricsConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyDefinition::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyListConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyResolver::class)]
#[UsesClass(\Guard\Config\Loc\ScanConfig::class)]
#[UsesClass(\Guard\Config\Loc\ScanConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Init\Legacy\Doc\ConfigLoader::class)]
#[UsesClass(\Guard\Init\Legacy\Doc\ReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\Doc\ReportConfigReader::class)]
#[UsesClass(\Guard\Init\Legacy\Loc\ConfigLoader::class)]
#[UsesClass(\Guard\Init\Legacy\Loc\ReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\Loc\ReportConfigReader::class)]
#[UsesClass(\Guard\Init\Legacy\Tree\ConfigLoader::class)]
#[UsesClass(\Guard\Init\Legacy\Tree\ReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\Tree\ReportConfigReader::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class LegacyMigrationTest extends TestCase
{
    public function testMigratePreservesLocGuardThresholdsDuringMigration(): void
    {
        $root = sys_get_temp_dir() . '/guard-migrate-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib], exclude: []}\npolicies:\n  strict:\n    limits: {file: {lines: 99}}\napply: {default: strict, rules: []}\n");
        $config = (new \Guard\Init\LegacyMigration())->migrate($root, ['version' => 1]);
        self::assertArrayNotHasKey('scope', $config);
        self::assertSame(['source' => ['lib'], 'exclude' => [], 'profiles' => ['strict' => ['limits' => ['file' => ['lines' => 99]]]], 'default' => 'strict', 'assignments' => []], $config['metrics']);
    }

    public function testReadPreservesLegacyMappings(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-legacy-');
        self::assertIsString($path);
        file_put_contents($path, 'scan: {roots: [lib]}');
        self::assertSame(['scan' => ['roots' => ['lib']]], (new \Guard\Init\LegacyMigration())->read($path));
    }
}
