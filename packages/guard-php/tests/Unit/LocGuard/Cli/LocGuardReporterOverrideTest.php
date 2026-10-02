<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardReporterOverride;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ScanConfig;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardReporterOverride
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 */
#[CoversClass(LocGuardReporterOverride::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScanConfig::class)]
final class LocGuardReporterOverrideTest extends TestCase
{
    public function testApplyReturnsConfigWithReporterOverride(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new LocGuardConfig(
            '/tmp/project',
            new ScanConfig(['src'], []),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path']),
        );

        self::assertSame('json', (new LocGuardReporterOverride())->apply($config, 'json')->report->reporter);
    }
}
