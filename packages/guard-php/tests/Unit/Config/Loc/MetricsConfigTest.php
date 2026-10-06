<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc;

use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\MetricsConfig;
use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Config\Loc\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\ScanConfig
 */
#[CoversClass(MetricsConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
final class MetricsConfigTest extends TestCase
{
    public function testStoresResolvedConfiguration(): void
    {
        $limits = new LimitConfig(500, 350, 400, 300, 200, 200, 50, 50, 20, 20);
        $config = new MetricsConfig(
            '/project',
            new ScanConfig(['src'], ['src/Generated/*']),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
        );

        self::assertSame('/project', $config->root);
        self::assertSame(['src'], $config->scan->roots);
        self::assertSame(['src/Generated/*'], $config->scan->exclude);
        self::assertSame($limits, $config->policies['standard']->limits);
        self::assertSame('standard', $config->apply->defaultPolicy);
    }
}
