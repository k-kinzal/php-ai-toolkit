<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Reporting\AiReporter;
use PhpAiToolkit\LocGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\LocGuard\Reporting\AiReportSummary;
use PhpAiToolkit\LocGuard\Reporting\AiViolationAction;
use PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\LocGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class AiReporterTest extends TestCase
{
    public function testReportFormatsPassingSummary(): void
    {
        $output = (new AiReporter())->report(
            new AnalysisResult([new FileMetric('src/A.php', 10, 7)], []),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('LOC_GUARD_PASSED', $output);
        self::assertStringContainsString('- violations: 0', $output);
    }

    public function testReportFormatsAiGuidance(): void
    {
        $output = (new AiReporter())->report(
            new AnalysisResult(
                [new FileMetric('src/A.php', 10, 7)],
                [new Violation('src/A.php', 2, 'file_ncloc', 7, 5, 'File has 7 non-comment lines of code; maximum is 5.')],
            ),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('LOC_GUARD_FAILED', $output);
        self::assertStringContainsString('guidance:', $output);
        self::assertStringContainsString('Reduce executable code', $output);
    }

    public function testReportFormatsActionsForViolationKinds(): void
    {
        $output = (new AiReporter())->report(
            new AnalysisResult(
                [new FileMetric('src/A.php', 10, 7)],
                [
                    new Violation('src/A.php', 1, 'cyclomatic_complexity', 21, 20, 'Complex.'),
                    new Violation('src/B.php', 1, 'file_lines', 501, 500, 'Large file.'),
                    new Violation('src/C.php', 3, 'method_lines', 51, 50, 'Long method.'),
                    new Violation('src/D.php', 2, 'class_lines', 401, 400, 'Large class.'),
                ],
            ),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('Reduce branch count', $output);
        self::assertStringContainsString('Reduce physical file size', $output);
        self::assertStringContainsString('Split the long function-like body', $output);
        self::assertStringContainsString('Reduce the oversized type', $output);
    }
}
