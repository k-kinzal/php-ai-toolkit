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
use Toolkit\LocGuard\Reporting\AiReporter;
use Toolkit\LocGuard\Reporting\AiReportGuidance;
use Toolkit\LocGuard\Reporting\AiReportSummary;
use Toolkit\LocGuard\Reporting\AiViolationAction;
use Toolkit\LocGuard\Reporting\AiViolationFormatter;
use Toolkit\LocGuard\Reporting\ViolationFieldComparator;
use Toolkit\LocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\LocGuard\Reporting\AiReporter
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\LocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\LocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\LocGuard\Analysis\Violation
 * @uses \Toolkit\LocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\LocGuard\Reporting\ViolationSorter
 */
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
