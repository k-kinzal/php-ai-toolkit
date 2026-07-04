<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViolationSorter::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
final class ViolationSorterTest extends TestCase
{
    public function testSortOrdersViolationsByConfiguredFields(): void
    {
        $violations = [
            new Violation('src/B.php', 1, 'file_lines', 10, 5, 'B'),
            new Violation('src/A.php', 9, 'method_lines', 9, 5, 'A9'),
            new Violation('src/A.php', 2, 'file_lines', 10, 5, 'A2'),
        ];

        $sorted = (new ViolationSorter())->sort($violations, new ReportConfig('text', ['path', 'line']));

        self::assertSame(['src/A.php:2', 'src/A.php:9', 'src/B.php:1'], array_map(static fn ($violation): string => $violation->path . ':' . $violation->line, $sorted));
    }

    public function testSortCanOrderByRuleAndActualValue(): void
    {
        $violations = [
            new Violation('src/A.php', 1, 'method_lines', 20, 10, 'A'),
            new Violation('src/B.php', 1, 'file_lines', 30, 10, 'B'),
            new Violation('src/C.php', 1, 'file_lines', 15, 10, 'C'),
        ];

        $sorted = (new ViolationSorter())->sort($violations, new ReportConfig('text', ['rule', 'actual']));

        self::assertSame(['file_lines:15', 'file_lines:30', 'method_lines:20'], array_map(static fn ($violation): string => $violation->rule . ':' . $violation->actual, $sorted));
    }

    public function testSortCanOrderByLimitAndReturnEqualComparison(): void
    {
        $violations = [
            new Violation('src/A.php', 1, 'file_lines', 30, 20, 'A'),
            new Violation('src/B.php', 1, 'file_lines', 20, 10, 'B'),
            new Violation('src/C.php', 1, 'file_lines', 15, 10, 'C'),
        ];

        $sorted = (new ViolationSorter())->sort($violations, new ReportConfig('text', ['limit']));

        self::assertSame([10, 10, 20], array_map(static fn ($violation): int => $violation->limit, $sorted));
    }
}
