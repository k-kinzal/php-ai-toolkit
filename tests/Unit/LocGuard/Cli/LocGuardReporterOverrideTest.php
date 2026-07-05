<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Cli\LocGuardReporterOverride;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocGuardReporterOverride::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(ReportConfig::class)]
final class LocGuardReporterOverrideTest extends TestCase
{
    public function testApplyReturnsConfigWithReporterOverride(): void
    {
        $config = new LocGuardConfig('/tmp/project', ['src'], [], new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20), new ReportConfig('ai', ['path']));

        self::assertSame('json', (new LocGuardReporterOverride())->apply($config, 'json')->report->reporter);
    }
}
