<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetricLimit;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
