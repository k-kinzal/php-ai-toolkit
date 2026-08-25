<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;

/**
 * @covers \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(Violation::class)]
final class FunctionMetricViolationBuilderTest extends TestCase
{
    public function testViolationsCombinesLineAndComplexityViolations(): void
    {
        $metric = new FunctionMetric('function', 'run', 2, 8, 3, 7);
        $metric->cyclomaticComplexity = 4;

        $violations = (new FunctionMetricViolationBuilder())->violations(
            'src/Example.php',
            [$metric],
            new LimitConfig(100, 100, 50, 50, 50, 50, 3, 50, 3),
        );

        self::assertSame(['function_lines', 'cyclomatic_complexity'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }
}
