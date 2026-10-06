<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\ClassLikeMetric;

use Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Config\Loc\LimitConfig;
use Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit;
use Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use Guard\Policy\Loc\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Guard\Policy\Loc\Violation
 */
#[CoversClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ClassLikeMetricLimit::class)]
#[UsesClass(Violation::class)]
final class ClassLikeMetricViolationBuilderTest extends TestCase
{
    public function testViolationsReturnsClassLikeLineViolation(): void
    {
        $violations = (new ClassLikeMetricViolationBuilder())->violations(
            'src/Example.php',
            [new ClassLikeMetric('class', 'Example', 3, 8)],
            new LimitConfig(100, 100, 3, 50, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame(['class_lines'], array_map(static fn ($violation): string => $violation->rule, $violations));
        self::assertSame(6, $violations[0]->actual);
    }

    public function testViolationsReturnsEmptyAtLimit(): void
    {
        $violations = (new ClassLikeMetricViolationBuilder())->violations(
            'src/Example.php',
            [new ClassLikeMetric('class', 'Example', 3, 5)],
            new LimitConfig(100, 100, 3, 50, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame([], $violations);
    }
}
