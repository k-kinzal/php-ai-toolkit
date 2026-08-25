<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\ClassLikeMetric;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfig
 * @uses \PhpAiToolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeMetricLimit::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(Violation::class)]
final class ClassLikeMetricViolationBuilderTest extends TestCase
{
    public function testViolationsReturnsClassLikeLineViolation(): void
    {
        $violations = (new ClassLikeMetricViolationBuilder())->violations(
            'src/Example.php',
            [new ClassLikeMetric('class', 'Example', 3, 8)],
            new LimitConfig(100, 100, 3, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame(['class_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
        self::assertSame(6, $violations[0]->actual);
    }

    public function testViolationsReturnsEmptyAtLimit(): void
    {
        $violations = (new ClassLikeMetricViolationBuilder())->violations(
            'src/Example.php',
            [new ClassLikeMetric('class', 'Example', 3, 5)],
            new LimitConfig(100, 100, 3, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame([], $violations);
    }
}
