<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\FunctionMetric;

use Guard\Collect\Php\FunctionMetric\FunctionMetric;
use Guard\Config\Loc\LimitConfig;
use Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder;
use Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder;
use Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder;
use Guard\Policy\Loc\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Loc\Violation
 */
#[CoversClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
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
            new LimitConfig(100, 100, 50, 50, 50, 50, 3, 50, 3, 3),
        );

        self::assertSame(['function_lines', 'cyclomatic_complexity'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }
}
