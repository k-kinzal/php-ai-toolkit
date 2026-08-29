<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;

/**
 * @covers \Toolkit\LocGuard\Config\LimitConfig
 */
#[CoversClass(LimitConfig::class)]
final class LimitConfigTest extends TestCase
{
    public function testStoresNumericThresholds(): void
    {
        $config = new LimitConfig(500, 350, 400, 300, 200, 190, 50, 55, 20, 20);

        self::assertSame(500, $config->maxFileLines);
        self::assertSame(350, $config->maxFileNcloc);
        self::assertSame(400, $config->maxClassLines);
        self::assertSame(300, $config->maxTraitLines);
        self::assertSame(200, $config->maxInterfaceLines);
        self::assertSame(190, $config->maxEnumLines);
        self::assertSame(50, $config->maxFunctionLines);
        self::assertSame(55, $config->maxMethodLines);
        self::assertSame(20, $config->maxFunctionCyclomaticComplexity);
        self::assertSame(20, $config->maxMethodCyclomaticComplexity);
    }

    public function testDisabledReturnsConfigurationWithoutEnabledLimits(): void
    {
        self::assertFalse(LimitConfig::disabled()->hasEnabledLimit());
    }

    public function testFromValuesCreatesConfigurationFromCanonicalMetricPaths(): void
    {
        $config = LimitConfig::fromValues([
            'file.lines' => 500,
            'method.cyclomatic_complexity' => 20,
        ]);

        self::assertSame(500, $config->maxFileLines);
        self::assertSame(20, $config->maxMethodCyclomaticComplexity);
        self::assertNull($config->maxFunctionLines);
    }

    public function testValuesReturnsEveryCanonicalMetricPath(): void
    {
        $values = LimitConfig::fromValues(['file.lines' => 500])->values();

        self::assertSame(500, $values['file.lines']);
        self::assertArrayHasKey('method.cyclomatic_complexity', $values);
    }

    public function testHasEnabledLimitDistinguishesEnabledAndDisabledPolicies(): void
    {
        self::assertTrue(LimitConfig::fromValues(['class.lines' => 400])->hasEnabledLimit());
        self::assertFalse(LimitConfig::disabled()->hasEnabledLimit());
    }
}
