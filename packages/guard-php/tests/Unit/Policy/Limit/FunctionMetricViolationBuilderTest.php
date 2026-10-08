<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Diagnostic\MetricViolation;
use Guard\Policy\Limit\FunctionComplexityViolationBuilder;
use Guard\Policy\Limit\FunctionLineViolationBuilder;
use Guard\Policy\Limit\FunctionMetricViolationBuilder;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Limit\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Diagnostic\MetricViolation
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 */
#[CoversClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(FunctionMetric::class)]
final class FunctionMetricViolationBuilderTest extends TestCase
{
    public function testViolationsCombinesLineAndComplexityViolations(): void
    {
        $metric = new FunctionMetric('function', 'run', 2, 8, 3, 7);
        $metric->cyclomaticComplexity = 4;

        $violations = (new FunctionMetricViolationBuilder())->violations(
            'src/Example.php',
            [$metric],
            new LimitConfig(100, 100, 50, 50, 50, 50, 3, 50, 3, 3),
        );

        self::assertSame(['function_lines', 'cyclomatic_complexity'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }
}
