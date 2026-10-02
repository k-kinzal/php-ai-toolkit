<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Reporting\JsonReporter;
use Toolkit\DocGuard\Reporting\ViolationFieldComparator;
use Toolkit\DocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\DocGuard\Reporting\JsonReporter
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\DocGuard\Reporting\ViolationSorter
 */
#[CoversClass(JsonReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class JsonReporterTest extends TestCase
{
    public function testReportEncodesStatusSummaryAndViolations(): void
    {
        $output = (new JsonReporter())->report(
            new AnalysisResult(1, 2, [new Violation('README.md', 4, 'renamed_heading', '## Usage', '## Überblick', 'Renamed.')]),
            new ReportConfig('json', ['path']),
        );

        self::assertSame([
            'status' => 'failed',
            'summary' => ['documents' => 1, 'headings' => 2, 'violations' => 1],
            'violations' => [[
                'path' => 'README.md',
                'line' => 4,
                'rule' => 'renamed_heading',
                'expected' => '## Usage',
                'actual' => '## Überblick',
                'message' => 'Renamed.',
            ]],
        ], json_decode($output, true));
        self::assertStringContainsString('Überblick', $output);
    }

    public function testReportMarksPassingResult(): void
    {
        $output = (new JsonReporter())->report(new AnalysisResult(1, 2, []), new ReportConfig('json', ['path']));

        self::assertStringContainsString('"status": "passed"', $output);
    }
}
