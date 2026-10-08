<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\ApplyConfig;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\MetricsConfig;
use Guard\Policy\Definition\PolicyConfig;
use Guard\Policy\Definition\ScanConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\MetricsConfig
 * @uses \Guard\Policy\Definition\ApplyConfig
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Definition\ScanConfig
 */
#[CoversClass(MetricsConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(\Guard\Policy\Definition\ApplyRuleConfig::class)]
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
