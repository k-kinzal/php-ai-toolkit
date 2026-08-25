<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\ClassLikeMetric;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;

/**
 * @covers \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Analysis\Violation
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
