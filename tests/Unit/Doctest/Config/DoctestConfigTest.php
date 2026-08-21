<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestConfig::class)]
#[UsesClass(ReportConfig::class)]
final class DoctestConfigTest extends TestCase
{
    public function testBootstrapPathResolvesRelativeToTheConfigDirectory(): void
    {
        $report = new ReportConfig('ai', ['path', 'line']);

        self::assertSame('/app/vendor/autoload.php', (new DoctestConfig('/app', ['src'], [], 'vendor/autoload.php', $report))->bootstrapPath());
        self::assertSame('/opt/boot.php', (new DoctestConfig('/app', ['src'], [], '/opt/boot.php', $report))->bootstrapPath());
        self::assertNull((new DoctestConfig('/app', ['src'], [], null, $report))->bootstrapPath());
    }

    public function testExposesTheResolvedConfiguration(): void
    {
        $report = new ReportConfig('ai', ['path', 'line']);
        $config = new DoctestConfig('/app', ['src'], ['src/Generated/*'], 'boot.php', $report);

        self::assertSame('/app', $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['src/Generated/*'], $config->exclude);
        self::assertSame('boot.php', $config->bootstrap);
        self::assertSame($report, $config->report);
    }
}
