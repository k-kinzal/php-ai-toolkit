<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Value;

use Guard\Config\Profile\ApplyConfig;
use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Value\LimitConfig;
use Guard\Config\Value\MetricsConfig;
use Guard\Config\Value\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Value\MetricsConfig
 * @uses \Guard\Config\Profile\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Config\Value\ScanConfig
 */
#[CoversClass(MetricsConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
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
