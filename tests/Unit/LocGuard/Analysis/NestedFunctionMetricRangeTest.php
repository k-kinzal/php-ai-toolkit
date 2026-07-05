<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\NestedFunctionMetricRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
