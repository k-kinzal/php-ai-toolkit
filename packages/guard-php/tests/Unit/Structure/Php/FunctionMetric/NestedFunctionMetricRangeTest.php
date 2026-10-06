<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\FunctionMetric;

use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
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
