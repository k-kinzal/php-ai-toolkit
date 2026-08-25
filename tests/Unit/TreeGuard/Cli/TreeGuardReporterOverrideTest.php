<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Cli\TreeGuardReporterOverride;
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Config\TreeGuardConfig;

/**
 * @covers \Toolkit\TreeGuard\Cli\TreeGuardReporterOverride
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 */
#[CoversClass(TreeGuardReporterOverride::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardConfig::class)]
final class TreeGuardReporterOverrideTest extends TestCase
{
    public function testApplyKeepsConfigWithoutOverride(): void
    {
        $config = new TreeGuardConfig('/project', ['src'], [], [], new ReportConfig('ai', ['path', 'rule']));

        self::assertSame($config, (new TreeGuardReporterOverride())->apply($config, null));
    }

    public function testApplyReplacesReporterAndKeepsEverythingElse(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $config = new TreeGuardConfig('/project', ['src'], ['*.tmp'], [$rule], new ReportConfig('ai', ['rule', 'path']));

        $overridden = (new TreeGuardReporterOverride())->apply($config, 'json');

        self::assertSame('json', $overridden->report->reporter);
        self::assertSame(['rule', 'path'], $overridden->report->orderBy);
        self::assertSame('/project', $overridden->root);
        self::assertSame(['src'], $overridden->paths);
        self::assertSame(['*.tmp'], $overridden->exclude);
        self::assertSame([$rule], $overridden->rules);
    }
}
