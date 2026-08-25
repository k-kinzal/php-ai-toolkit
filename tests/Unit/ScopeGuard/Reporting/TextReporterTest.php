<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\ScopeGuard\Analysis\Violation;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Reporting\TextReporter;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Reporting\TextReporter
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Violation
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \PhpAiToolkit\ScopeGuard\Reporting\ViolationSorter
 */
#[CoversClass(TextReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class TextReporterTest extends TestCase
{
    public function testReportAnnouncesAPassingRun(): void
    {
        $report = (new TextReporter())->report(new AnalysisResult(4, 2, 9, []), new ReportConfig('text', ['path', 'line']));

        self::assertStringStartsWith('ScopeGuard passed.', $report);
    }

    public function testReportCountsTheViolations(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');
        $report = (new TextReporter())->report(new AnalysisResult(4, 2, 9, [$violation]), new ReportConfig('text', ['path', 'line']));

        self::assertStringStartsWith("ScopeGuard found 1 violations.\n", $report);
    }

    public function testReportPrintsEachViolationWithItsLocation(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');
        $report = (new TextReporter())->report(new AnalysisResult(4, 2, 9, [$violation]), new ReportConfig('text', ['path', 'line']));

        self::assertStringContainsString("src/A.php:21 [out_of_scope]\n  Not visible.\n", $report);
    }
}
