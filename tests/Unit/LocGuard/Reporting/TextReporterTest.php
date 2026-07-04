<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Reporting\TextReporter;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
