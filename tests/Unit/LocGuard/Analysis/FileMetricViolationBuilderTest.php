<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\FileMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
            new LimitConfig(10, 7, 50, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame(['file_lines', 'file_ncloc'], array_map(static fn ($violation): string => $violation->rule, $violations));
    }

    public function testViolationsReturnsEmptyAtLimits(): void
    {
        $violations = (new FileMetricViolationBuilder())->violations(
            new FileMetric('src/Example.php', 10, 7),
            new LimitConfig(10, 7, 50, 50, 50, 50, 50, 50, 50),
        );

        self::assertSame([], $violations);
    }
}
