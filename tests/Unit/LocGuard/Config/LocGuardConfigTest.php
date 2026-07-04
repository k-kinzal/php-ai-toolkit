<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocGuardConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ReportConfig::class)]
final class LocGuardConfigTest extends TestCase
{
    public function testStoresResolvedConfiguration(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20);
        $report = new ReportConfig('ai', ['path', 'line']);
        $config = new LocGuardConfig('/project', ['src'], ['src/Generated/*'], $limits, $report);

        self::assertSame('/project', $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['src/Generated/*'], $config->exclude);
        self::assertSame($limits, $config->limits);
        self::assertSame($report, $config->report);
    }
}
