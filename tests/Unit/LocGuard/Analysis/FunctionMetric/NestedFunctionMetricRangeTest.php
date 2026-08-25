<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange;

/**
 * @covers \Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 */
#[CoversClass(NestedFunctionMetricRange::class)]
#[UsesClass(FunctionMetric::class)]
final class NestedFunctionMetricRangeTest extends TestCase
{
    public function testContainsReturnsTrueForNestedMetricRange(): void
    {
        $outer = new FunctionMetric('function', 'outer', 1, 10, 2, 20);
        $inner = new FunctionMetric('function', 'inner', 4, 6, 8, 12);

        self::assertTrue((new NestedFunctionMetricRange())->contains(10, $outer, [$outer, $inner]));
    }

    public function testContainsReturnsFalseForCurrentMetric(): void
    {
        $outer = new FunctionMetric('function', 'outer', 1, 10, 2, 20);

        self::assertFalse((new NestedFunctionMetricRange())->contains(10, $outer, [$outer]));
    }
}
