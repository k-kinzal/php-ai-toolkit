<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\FunctionComplexityViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionLineViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
