<?php

declare(strict_types=1);

namespace Tests\Unit\Lint;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Report;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 */
#[CoversClass(Report::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
final class ReportTest extends TestCase
{
    public function testCountsGroupsFindingsBySeverity(): void
    {
        $report = new Report([
            new Finding('a.php', 1, 1, 'r', 'error', 's', 'm', 'f', 1.0),
            new Finding('a.php', 2, 1, 'r', 'warning', 's', 'm', 'f', 1.0),
            new Finding('b.php', 1, 1, 'r', 'warning', 's', 'm', 'f', 1.0),
            new Finding('b.php', 3, 1, 'r', 'info', 's', 'm', 'f', 1.0),
        ], 3, 10, 7, ['jev-1.13.0'], 'error');
        self::assertSame(['error' => 1, 'warning' => 2, 'info' => 1], $report->counts());
        self::assertSame(3, $report->files);
        self::assertSame(10, $report->targets);
        self::assertSame(7, $report->requests);
        self::assertSame(['jev-1.13.0'], $report->models);
        self::assertSame('error', $report->failOn);
    }

    public function testFailedComparesTheStrongestFindingWithTheThreshold(): void
    {
        $warning = new Finding('a.php', 1, 1, 'r', 'warning', 's', 'm', 'f', 1.0);
        self::assertFalse((new Report([$warning], 1, 1, 1, [], 'error'))->failed());
        self::assertTrue((new Report([$warning], 1, 1, 1, [], 'warning'))->failed());
        self::assertTrue((new Report([$warning], 1, 1, 1, [], 'info'))->failed());
        self::assertFalse((new Report([$warning], 1, 1, 1, [], 'never'))->failed());
        self::assertFalse((new Report([], 1, 1, 1, [], 'info'))->failed());
    }

    public function testFilesWithFindingsCountsDistinctPaths(): void
    {
        $report = new Report([
            new Finding('a.php', 1, 1, 'r', 'error', 's', 'm', 'f', 1.0),
            new Finding('a.php', 2, 1, 'r', 'error', 's', 'm', 'f', 1.0),
            new Finding('b.php', 1, 1, 'r', 'error', 's', 'm', 'f', 1.0),
        ], 5, 5, 5, [], 'error');
        self::assertSame(2, $report->filesWithFindings());
    }
}
