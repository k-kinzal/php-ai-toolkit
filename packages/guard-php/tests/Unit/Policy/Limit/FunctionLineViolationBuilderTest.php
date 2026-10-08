<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Diagnostic\MetricViolation;
use Guard\Policy\Limit\FunctionLineViolationBuilder;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Diagnostic\MetricViolation
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 */
#[CoversClass(FunctionLineViolationBuilder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(FunctionMetric::class)]
final class FunctionLineViolationBuilderTest extends TestCase
{
    public function testViolationsReturnsMethodLineViolation(): void
    {
        $violations = (new FunctionLineViolationBuilder())->violations(
            'src/Example.php',
            new FunctionMetric('method', 'Example::run', 2, 8, 3, 7),
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 3, 50, 50),
        );

        self::assertSame(['method_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }

    public function testViolationsReturnsEmptyAtLimit(): void
    {
        $violations = (new FunctionLineViolationBuilder())->violations(
            'src/Example.php',
            new FunctionMetric('function', 'run', 2, 4, 3, 4),
            new LimitConfig(100, 100, 50, 50, 50, 50, 3, 50, 50, 50),
        );

        self::assertSame([], $violations);
    }
}
