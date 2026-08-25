<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;

/**
 * @covers \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(FunctionLineViolationBuilder::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(Violation::class)]
final class FunctionLineViolationBuilderTest extends TestCase
{
    public function testViolationsReturnsMethodLineViolation(): void
    {
        $violations = (new FunctionLineViolationBuilder())->violations(
            'src/Example.php',
            new FunctionMetric('method', 'Example::run', 2, 8, 3, 7),
            new LimitConfig(100, 100, 50, 50, 50, 50, 50, 3, 50),
        );

        self::assertSame(['method_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }

    public function testViolationsReturnsEmptyAtLimit(): void
    {
        $violations = (new FunctionLineViolationBuilder())->violations(
            'src/Example.php',
            new FunctionMetric('function', 'run', 2, 4, 3, 4),
            new LimitConfig(100, 100, 50, 50, 50, 50, 3, 50, 50),
        );

        self::assertSame([], $violations);
    }
}
