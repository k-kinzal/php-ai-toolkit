<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use Guard\Config\Value\LimitConfig;
use Guard\Policy\Limit\FunctionComplexityViolationBuilder;
use Guard\Reporting\MetricViolation;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\FunctionComplexityViolationBuilder
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Reporting\MetricViolation
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 */
#[CoversClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(FunctionMetric::class)]
final class FunctionComplexityViolationBuilderTest extends TestCase
{
    public function testViolationReturnsComplexityViolation(): void
    {
        $metric = new FunctionMetric('function', 'run', 2, 4, 3, 4);
        $metric->cyclomaticComplexity = 4;

        $violation = (new FunctionComplexityViolationBuilder())->violation(
            'src/Example.php',
            $metric,
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 50, 3, 3),
        );

        self::assertInstanceOf(MetricViolation::class, $violation);
        self::assertSame('cyclomatic_complexity', $violation->rule);
    }

    public function testViolationReturnsNullAtLimit(): void
    {
        $metric = new FunctionMetric('function', 'run', 2, 4, 3, 4);
        $metric->cyclomaticComplexity = 3;

        self::assertNull((new FunctionComplexityViolationBuilder())->violation(
            'src/Example.php',
            $metric,
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 50, 3, 3),
        ));
    }

    public function testViolationUsesSeparateMethodComplexityLimit(): void
    {
        $metric = new FunctionMetric('method', 'run', 2, 4, 3, 4);
        $metric->cyclomaticComplexity = 4;

        $violation = (new FunctionComplexityViolationBuilder())->violation(
            'src/Example.php',
            $metric,
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 50, 10, 3),
            'strict',
        );

        self::assertInstanceOf(MetricViolation::class, $violation);
        self::assertSame(3, $violation->limit);
        self::assertSame('strict', $violation->policy);
    }

    public function testViolationReturnsNullWhenMetricIsDisabled(): void
    {
        $metric = new FunctionMetric('function', 'run', 2, 4, 3, 4);
        $metric->cyclomaticComplexity = 100;

        self::assertNull((new FunctionComplexityViolationBuilder())->violation(
            'src/Example.php',
            $metric,
            LimitConfig::disabled(),
        ));
    }
}
