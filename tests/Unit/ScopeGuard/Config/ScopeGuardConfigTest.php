<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;

/**
 * @covers \Toolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 */
#[CoversClass(ScopeGuardConfig::class)]
#[UsesClass(ReportConfig::class)]
final class ScopeGuardConfigTest extends TestCase
{
    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testRootIsReadable(ScopeGuardConfig $config): void
    {
        self::assertSame('/project', $config->root);
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testPathsAreReadable(ScopeGuardConfig $config): void
    {
        self::assertSame(['src'], $config->paths);
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testExcludeIsReadable(ScopeGuardConfig $config): void
    {
        self::assertSame(['src/Generated/*'], $config->exclude);
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testExemptNamespacesAreReadable(ScopeGuardConfig $config): void
    {
        self::assertSame(['Tests'], $config->exemptNamespaces);
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testReportIsReadable(ScopeGuardConfig $config): void
    {
        self::assertSame('ai', $config->report->reporter);
    }


    /**
     * @return array<string, array{ScopeGuardConfig}>
     */
    public static function providerConfig(): array
    {
        return ['a fully populated configuration' => [new ScopeGuardConfig(
            '/project',
            ['src'],
            ['src/Generated/*'],
            ['Tests'],
            new ReportConfig('ai', ['path', 'line']),
        )]];
    }
}
