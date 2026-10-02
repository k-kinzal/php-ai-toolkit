<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\AnalysisResult;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetric;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Reporting\TextReporter;
use Toolkit\LocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\LocGuard\Reporting\TextReporter
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Analysis\Violation
 * @uses \Toolkit\LocGuard\Reporting\ViolationSorter
 */
#[CoversClass(TextReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationSorter::class)]
final class TextReporterTest extends TestCase
{
    public function testReportFormatsPassingResult(): void
    {
        $output = (new TextReporter())->report(
            new AnalysisResult([new FileMetric('src/A.php', 10, 7)], []),
            new ReportConfig('text', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('LocGuard passed. No violations found.', $output);
        self::assertStringContainsString('Summary: 1 files, 10 physical lines, 7 NCLOC.', $output);
    }

    public function testReportFormatsViolations(): void
    {
        $output = (new TextReporter())->report(
            new AnalysisResult(
                [new FileMetric('src/A.php', 10, 7)],
                [new Violation('src/A.php', 4, 'function_lines', 51, 50, 'function run has 51 physical lines; maximum is 50.')],
            ),
            new ReportConfig('text', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('LocGuard found 1 violations.', $output);
        self::assertStringContainsString('src/A.php:4 [function_lines]', $output);
        self::assertStringContainsString('Actual: 51, Limit: 50', $output);
    }
}
