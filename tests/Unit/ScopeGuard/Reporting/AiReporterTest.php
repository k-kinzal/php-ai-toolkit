<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\ScopeGuard\Analysis\Violation;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Reporting\AiReporter;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary;
use PhpAiToolkit\ScopeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\ScopeGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiReportGuidance
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Violation
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter
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
