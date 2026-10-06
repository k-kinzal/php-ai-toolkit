<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\FileMetric;

use Guard\Collect\Php\FileMetric\FileMetric;
use Guard\Config\Loc\LimitConfig;
use Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder;
use Guard\Policy\Loc\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder
 * @uses \Guard\Collect\Php\FileMetric\FileMetric
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Policy\Loc\Violation
 */
#[CoversClass(FileMetricViolationBuilder::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(Violation::class)]
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
