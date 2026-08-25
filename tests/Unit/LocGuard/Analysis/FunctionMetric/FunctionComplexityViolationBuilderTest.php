<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfig
 * @uses \PhpAiToolkit\LocGuard\Analysis\Violation
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
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 50, 3),
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
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 50, 3),
        ));
    }
}
