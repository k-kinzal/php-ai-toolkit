<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Cli\ScopeGuardReporterOverride;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;

/**
 * @covers \Toolkit\ScopeGuard\Cli\ScopeGuardReporterOverride
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Config\ScopeGuardConfig
 */
#[CoversClass(ScopeGuardReporterOverride::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeGuardConfig::class)]
final class ScopeGuardReporterOverrideTest extends TestCase
{
    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testApplyReplacesTheConfiguredReporter(ScopeGuardConfig $config): void
    {
        self::assertSame('json', (new ScopeGuardReporterOverride())->apply($config, 'json')->report->reporter);
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testApplyKeepsTheConfiguredOrdering(ScopeGuardConfig $config): void
    {
        self::assertSame(['path', 'line'], (new ScopeGuardReporterOverride())->apply($config, 'json')->report->orderBy);
    }

    /**
     * @dataProvider providerConfig
     */
    #[DataProvider('providerConfig')]
    public function testApplyReturnsTheConfigUnchangedWithoutOverride(ScopeGuardConfig $config): void
    {
        self::assertSame($config, (new ScopeGuardReporterOverride())->apply($config, null));
    }

    /**
     * @return array<string, array{ScopeGuardConfig}>
     */
    public static function providerConfig(): array
    {
        return ['a config reporting to the AI reporter' => [new ScopeGuardConfig(
            '/project',
            ['src'],
            [],
            ['Tests'],
            new ReportConfig('ai', ['path', 'line']),
        )]];
    }
}
