<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\LegacyMigration
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\ConfigLoader
 * @uses \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Config\ReportConfigReader
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ConfigLoader
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
 * @uses \Toolkit\LocGuard\Config\ReportConfigReader
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfigReader
 * @uses \Toolkit\LocGuard\LocGuardException
 * @uses \Toolkit\TreeGuard\Config\ConfigLoader
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\ReportConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(\Toolkit\Guard\Init\LegacyMigration::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigLoader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeading::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeadingReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentListConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ReportConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\DocGuardException::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\DocGuardPathResolver::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Heading::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigLoader::class)]
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
#[UsesClass(\Toolkit\LocGuard\Config\ReportConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ScanConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ScanConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\LocGuardException::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigLoader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ReportConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleListConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\TreeGuardException::class)]
final class LegacyMigrationTest extends TestCase
{
    public function testMigratePreservesLocGuardThresholdsDuringMigration(): void
    {
        $root = sys_get_temp_dir() . '/guard-migrate-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib], exclude: []}\npolicies:\n  strict:\n    limits: {file: {lines: 99}}\napply: {default: strict, rules: []}\n");
        $config = (new \Toolkit\Guard\Init\LegacyMigration())->migrate($root, ['version' => 1]);
        self::assertSame(['source' => ['lib'], 'exclude' => []], $config['scope']);
        self::assertSame(['profiles' => ['strict' => ['limits' => ['file' => ['lines' => 99]]]], 'default' => 'strict', 'assignments' => []], $config['quality']);
    }

    public function testReadPreservesLegacyMappings(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-legacy-');
        self::assertIsString($path);
        file_put_contents($path, 'scan: {roots: [lib]}');
        self::assertSame(['scan' => ['roots' => ['lib']]], (new \Toolkit\Guard\Init\LegacyMigration())->read($path));
    }
}
