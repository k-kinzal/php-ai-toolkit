<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Reporting\AiReporter;
use Toolkit\ScopeGuard\Reporting\AiReportGuidance;
use Toolkit\ScopeGuard\Reporting\AiReportSummary;
use Toolkit\ScopeGuard\Reporting\AiViolationAction;
use Toolkit\ScopeGuard\Reporting\AiViolationFormatter;
use Toolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use Toolkit\ScopeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\AiReporter
 * @uses \Toolkit\ScopeGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationSorter
 */
#[CoversClass(AiReporter::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class AiReporterTest extends TestCase
{
    public function testReportOpensWithThePassMarker(): void
    {
        $report = (new AiReporter())->report(new AnalysisResult(4, 2, 9, []), new ReportConfig('ai', ['path', 'line']));

        self::assertStringStartsWith("SCOPE_GUARD_PASSED\n", $report);
    }

    public function testReportOpensWithTheFailMarker(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');
        $report = (new AiReporter())->report(new AnalysisResult(4, 2, 9, [$violation]), new ReportConfig('ai', ['path', 'line']));

        self::assertStringStartsWith("SCOPE_GUARD_FAILED\n", $report);
    }

    public function testReportOmitsGuidanceWhenNothingFailed(): void
    {
        $report = (new AiReporter())->report(new AnalysisResult(4, 2, 9, []), new ReportConfig('ai', ['path', 'line']));

        self::assertStringNotContainsString('guidance:', $report);
    }

    public function testReportNumbersEveryViolation(): void
    {
        $first = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');
        $second = new Violation('src/B.php', 3, 'invalid_scope', 'App\\B', 'Unusable.');
        $report = (new AiReporter())->report(new AnalysisResult(4, 2, 9, [$first, $second]), new ReportConfig('ai', ['path', 'line']));

        self::assertStringContainsString("2. src/B.php:3 [invalid_scope]\n", $report);
    }
}
