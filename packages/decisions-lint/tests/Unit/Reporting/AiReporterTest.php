<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\AiReporter;
use DecisionsLint\Reporting\Summary;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\AiReporter
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Reporting\Summary
 */
#[CoversClass(AiReporter::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
#[UsesClass(Report::class)]
#[UsesClass(Summary::class)]
final class AiReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderWritesOneLinePerFindingThenTheSummary(): void
    {
        $report = new Report([new Finding('src/A.php', 12, 5, 'cohesion.method', 'warning', 'App\A::foo', 'Misplaced.', 'Move it.', 0.9)], 1, 2, 1, ['jev-1.13.0'], 'error');
        self::assertSame(
            "warning: src/A.php:12:5 [cohesion.method] App\\A::foo confidence=0.90 Misplaced. Fix: Move it.\n0 errors, 1 warning, 0 infos, 1 file with findings, 2 targets, 1 request to jev-1.13.0.\nDecisions check passed.\n",
            (new AiReporter(new Summary()))->render($report, true),
        );
    }

    /**
     * @throws JsonException
     */
    public function testRenderReportsAPassingRunWithoutFindings(): void
    {
        self::assertSame("0 errors, 0 warnings, 0 infos, 0 files with findings, 0 targets, 0 requests.\nDecisions check passed.\n", (new AiReporter())->render(new Report([], 0, 0, 0, [], 'error'), false));
    }
}
