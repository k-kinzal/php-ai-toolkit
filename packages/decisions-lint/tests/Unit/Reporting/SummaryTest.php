<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\Summary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\Summary
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Lint\Report
 */
#[CoversClass(Summary::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
#[UsesClass(Report::class)]
final class SummaryTest extends TestCase
{
    public function testCountsJoinsEveryNumberWithItsNoun(): void
    {
        $report = new Report([new Finding('a.php', 1, 1, 'r', 'error', 's', 'm', 'f', 1.0), new Finding('a.php', 2, 1, 'r', 'warning', 's', 'm', 'f', 1.0)], 3, 14, 9, ['jev-1.13.0'], 'error');
        self::assertSame('1 error, 1 warning, 0 infos, 1 file with findings, 14 targets, 9 requests to jev-1.13.0', (new Summary())->counts($report, ', '));
        self::assertSame('0 errors · 0 warnings · 0 infos · 0 files with findings · 1 target · 1 request', (new Summary())->counts(new Report([], 1, 1, 1, [], 'error'), ' · '));
    }

    public function testVerdictNamesTheThresholdWhenTheRunFails(): void
    {
        $failed = new Report([new Finding('a.php', 1, 1, 'r', 'warning', 's', 'm', 'f', 1.0)], 1, 1, 1, [], 'warning');
        self::assertSame('Decisions check failed: a finding is at least warning and fail_on is warning.', (new Summary())->verdict($failed));
        self::assertSame('Decisions check passed.', (new Summary())->verdict(new Report([], 1, 1, 1, [], 'error')));
    }

    public function testPluralPicksTheNoun(): void
    {
        self::assertSame('1 file', (new Summary())->plural(1, 'file', 'files'));
        self::assertSame('0 files', (new Summary())->plural(0, 'file', 'files'));
    }
}
