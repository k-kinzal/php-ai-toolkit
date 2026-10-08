<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Diagnostic\MetricViolation;
use Guard\Policy\Limit\ClassLikeMetricLimit;
use Guard\Policy\Limit\ClassLikeMetricViolationBuilder;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Limit\ClassLikeMetricLimit
 * @uses \Guard\Policy\Diagnostic\MetricViolation
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 */
#[CoversClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ClassLikeMetricLimit::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(ClassLikeMetric::class)]
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
