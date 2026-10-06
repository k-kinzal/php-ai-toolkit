<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\FunctionMetric;

use Guard\Collect\Php\FunctionMetric\FunctionMetric;
use Guard\Config\Loc\LimitConfig;
use Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder;
use Guard\Policy\Loc\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Policy\Loc\Violation
 */
#[CoversClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(Violation::class)]
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

        self::assertInstanceOf(Violation::class, $violation);
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

        self::assertInstanceOf(Violation::class, $violation);
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
