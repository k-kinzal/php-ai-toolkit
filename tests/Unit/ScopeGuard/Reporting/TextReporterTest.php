<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Reporting\TextReporter;
use Toolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use Toolkit\ScopeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\TextReporter
 * @uses \Toolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationSorter
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
