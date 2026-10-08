<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\LegacyMigration
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
 * @uses \Guard\Policy\Definition\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyConfigReader
 * @uses \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfigReader
 * @uses \Guard\Config\Profile\ApplyRuleListConfigReader
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Config\Profile\PolicyConfigReader
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Config\Profile\PolicyListConfigReader
 * @uses \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Config\Reader\ScanConfigReader
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DirectoryRuleConfig
 * @uses \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Definition\DocumentationConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Definition\MetricsConfig
 * @uses \Guard\Policy\Definition\ScanConfig
 * @uses \Guard\Policy\Definition\StructureConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Config\Project\Legacy\DirectoryConfiguration
 * @uses \Guard\Config\Project\Legacy\DirectoryReportConfig
 * @uses \Guard\Config\Project\Legacy\DirectoryReportReader
 * @uses \Guard\Config\Project\Legacy\HeadingConfiguration
 * @uses \Guard\Config\Project\Legacy\HeadingReportConfig
 * @uses \Guard\Config\Project\Legacy\HeadingReportReader
 * @uses \Guard\Config\Project\Legacy\MetricConfiguration
 * @uses \Guard\Config\Project\Legacy\MetricReportConfig
 * @uses \Guard\Config\Project\Legacy\MetricReportReader
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 */
#[CoversClass(\Guard\Config\Project\LegacyMigration::class)]
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
#[UsesClass(\Guard\Policy\Definition\ApplyConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Policy\Definition\ApplyRuleConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Policy\Definition\PolicyConfig::class)]
#[UsesClass(\Guard\Config\Profile\PolicyConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\PolicyDefinition::class)]
#[UsesClass(\Guard\Config\Profile\PolicyListConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\PolicyResolver::class)]
#[UsesClass(\Guard\Config\Reader\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\ScanConfigReader::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Validation\HeadingConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Validation\HeadingConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Validation\MetricConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Validation\MetricConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Validation\MetricConfigStringListReader::class)]
#[UsesClass(\Guard\Policy\Definition\DeclaredHeading::class)]
#[UsesClass(\Guard\Policy\Definition\DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Policy\Definition\DocumentConfig::class)]
#[UsesClass(\Guard\Policy\Definition\DocumentationConfig::class)]
#[UsesClass(\Guard\Policy\Definition\LimitConfig::class)]
#[UsesClass(\Guard\Policy\Definition\MetricsConfig::class)]
#[UsesClass(\Guard\Policy\Definition\ScanConfig::class)]
#[UsesClass(\Guard\Policy\Definition\StructureConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Config\Project\Legacy\DirectoryConfiguration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\DirectoryReportConfig::class)]
#[UsesClass(\Guard\Config\Project\Legacy\DirectoryReportReader::class)]
#[UsesClass(\Guard\Config\Project\Legacy\HeadingConfiguration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\HeadingReportConfig::class)]
#[UsesClass(\Guard\Config\Project\Legacy\HeadingReportReader::class)]
#[UsesClass(\Guard\Config\Project\Legacy\MetricConfiguration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\MetricReportConfig::class)]
#[UsesClass(\Guard\Config\Project\Legacy\MetricReportReader::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
#[UsesClass(\Guard\Structure\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\Heading::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingTextNormalizer::class)]
final class LegacyMigrationTest extends TestCase
{
    public function testMigratePreservesLocGuardThresholdsDuringMigration(): void
    {
        $root = sys_get_temp_dir() . '/guard-migrate-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib], exclude: []}\npolicies:\n  strict:\n    limits: {file: {lines: 99}}\napply: {default: strict, rules: []}\n");
        $config = (new \Guard\Config\Project\LegacyMigration())->migrate($root, ['version' => 1]);
        self::assertArrayNotHasKey('scope', $config);
        self::assertSame(['source' => ['lib'], 'exclude' => [], 'profiles' => ['strict' => ['limits' => ['file' => ['lines' => 99]]]], 'default' => 'strict', 'assignments' => []], $config['metrics']);
    }

    public function testReadPreservesLegacyMappings(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-legacy-');
        self::assertIsString($path);
        file_put_contents($path, 'scan: {roots: [lib]}');
        self::assertSame(['scan' => ['roots' => ['lib']]], (new \Guard\Config\Project\LegacyMigration())->read($path));
    }
}
