<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use Guard\Config\Value\LimitConfig;
use Guard\Policy\Limit\FileMetricViolationBuilder;
use Guard\Reporting\MetricViolation;
use Guard\Structure\Php\FileMetric\FileMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\FileMetricViolationBuilder
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Reporting\MetricViolation
 * @uses \Guard\Structure\Php\FileMetric\FileMetric
 */
#[CoversClass(FileMetricViolationBuilder::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(FileMetric::class)]
final class FileMetricViolationBuilderTest extends TestCase
{
    public function testViolationsReturnsFileLineAndNclocViolations(): void
    {
        $violations = (new FileMetricViolationBuilder())->violations(
            new FileMetric('src/Example.php', 12, 8),
            new LimitConfig(10, 7, 50, 50, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame(['file_lines', 'file_ncloc'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }

    public function testViolationsReturnsEmptyAtLimits(): void
    {
        $violations = (new FileMetricViolationBuilder())->violations(
            new FileMetric('src/Example.php', 10, 7),
            new LimitConfig(10, 7, 50, 50, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame([], $violations);
    }
}
