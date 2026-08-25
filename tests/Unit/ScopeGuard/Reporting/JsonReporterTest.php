<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Reporting\JsonReporter;
use Toolkit\ScopeGuard\Reporting\ViolationFieldComparator;
use Toolkit\ScopeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\JsonReporter
 * @uses \Toolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\ScopeGuard\Reporting\ViolationSorter
 */
#[CoversClass(JsonReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class JsonReporterTest extends TestCase
{
    public function testReportMarksAPassingRun(): void
    {
        $report = (new JsonReporter())->report(new AnalysisResult(4, 2, 9, []), new ReportConfig('json', ['path', 'line']));

        self::assertStringContainsString('"status": "passed"', $report);
    }

    public function testReportMarksAFailedRun(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');
        $report = (new JsonReporter())->report(new AnalysisResult(4, 2, 9, [$violation]), new ReportConfig('json', ['path', 'line']));

        self::assertStringContainsString('"status": "failed"', $report);
    }

    public function testReportCarriesEveryFieldOfAFailedRun(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');
        $report = (new JsonReporter())->report(new AnalysisResult(4, 2, 9, [$violation]), new ReportConfig('json', ['path', 'line']));

        $expected = <<<'JSON'
            {
                "status": "failed",
                "summary": {
                    "files": 4,
                    "scoped_declarations": 2,
                    "references": 9,
                    "violations": 1
                },
                "violations": [
                    {
                        "path": "src/A.php",
                        "line": 21,
                        "rule": "out_of_scope",
                        "symbol": "App\\A",
                        "message": "Not visible."
                    }
                ]
            }
            JSON;

        self::assertSame($expected . "\n", $report);
    }

    public function testReportSummarisesAPassingRun(): void
    {
        $report = (new JsonReporter())->report(new AnalysisResult(4, 2, 9, []), new ReportConfig('json', ['path', 'line']));

        $expected = <<<'JSON'
            {
                "status": "passed",
                "summary": {
                    "files": 4,
                    "scoped_declarations": 2,
                    "references": 9,
                    "violations": 0
                },
                "violations": []
            }
            JSON;

        self::assertSame($expected . "\n", $report);
    }
}
