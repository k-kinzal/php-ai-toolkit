<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ScanConfig;

/**
 * @covers \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 */
#[CoversClass(LocGuardConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScanConfig::class)]
final class LocGuardConfigTest extends TestCase
{
    public function testStoresResolvedConfiguration(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $report = new ReportConfig('ai', ['path', 'line']);
        $config = new LocGuardConfig(
            '/project',
            new ScanConfig(['src'], ['src/Generated/*']),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            $report,
        );

        self::assertSame('/project', $config->root);
        self::assertSame(['src'], $config->scan->roots);
        self::assertSame(['src/Generated/*'], $config->scan->exclude);
        self::assertSame($limits, $config->policies['standard']->limits);
        self::assertSame('standard', $config->apply->defaultPolicy);
        self::assertSame($report, $config->report);
    }
}
