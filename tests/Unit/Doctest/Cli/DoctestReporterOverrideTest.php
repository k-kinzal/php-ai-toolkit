<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\DoctestReporterOverride;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestReporterOverride::class)]
#[UsesClass(DoctestConfig::class)]
#[UsesClass(ReportConfig::class)]
final class DoctestReporterOverrideTest extends TestCase
{
    public function testApplyReplacesTheReporterAndKeepsEverythingElse(): void
    {
        $config = new DoctestConfig('/app', ['src'], ['skip/*'], 'boot.php', new ReportConfig('ai', ['path', 'line']));

        $overridden = (new DoctestReporterOverride())->apply($config, 'json');

        self::assertSame('json', $overridden->report->reporter);
        self::assertSame(['path', 'line'], $overridden->report->orderBy);
        self::assertSame(['src'], $overridden->paths);
        self::assertSame(['skip/*'], $overridden->exclude);
        self::assertSame('boot.php', $overridden->bootstrap);
        self::assertSame('/app', $overridden->root);
    }

    public function testApplyReturnsTheConfigUnchangedWithoutAnOverride(): void
    {
        $config = new DoctestConfig('/app', ['src'], [], null, new ReportConfig('ai', ['path']));

        self::assertSame($config, (new DoctestReporterOverride())->apply($config, null));
    }
}
