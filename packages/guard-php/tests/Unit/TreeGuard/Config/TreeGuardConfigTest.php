<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Config\TreeGuardConfig;

/**
 * @covers \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 */
#[CoversClass(TreeGuardConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(RuleConfig::class)]
final class TreeGuardConfigTest extends TestCase
{
    public function testStoresConfigData(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);
        $report = new ReportConfig('ai', ['path', 'rule']);
        $config = new TreeGuardConfig('/project', ['src'], ['*.tmp'], [$rule], $report);

        self::assertSame('/project', $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['*.tmp'], $config->exclude);
        self::assertSame([$rule], $config->rules);
        self::assertSame($report, $config->report);
    }
}
