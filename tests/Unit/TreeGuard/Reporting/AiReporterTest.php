<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\ReportConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Reporting\AiReporter;
use PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\TreeGuard\Reporting\AiReportSummary;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\TreeGuard\Reporting\ViolationFieldComparator;
use PhpAiToolkit\TreeGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \PhpAiToolkit\TreeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\TreeGuard\Analysis\Violation
 * @uses \PhpAiToolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \PhpAiToolkit\TreeGuard\Reporting\ViolationSorter
 */
#[CoversClass(AiReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class AiReporterTest extends TestCase
{
    public function testReportFormatsPassingSummary(): void
    {
        $output = (new AiReporter())->report(
            new AnalysisResult(['src' => new DirectoryListing('src', ['A.php'], [])], []),
            new ReportConfig('ai', ['path', 'rule']),
        );

        self::assertStringContainsString('TREE_GUARD_PASSED', $output);
        self::assertStringContainsString('- violations: 0', $output);
        self::assertStringNotContainsString('guidance:', $output);
    }

    public function testReportFormatsSortedViolationsWithGuidance(): void
    {
        $output = (new AiReporter())->report(
            new AnalysisResult(
                ['src' => new DirectoryListing('src', ['A.php'], [])],
                [
                    new Violation('src/B', 'max_files', 'src/*', 3, 1, 'B has too many files.'),
                    new Violation('src/A', 'max_files', 'src/*', 2, 1, 'A has too many files.'),
                ],
            ),
            new ReportConfig('ai', ['path', 'rule']),
        );

        self::assertStringContainsString('TREE_GUARD_FAILED', $output);
        self::assertStringContainsString('guidance:', $output);
        self::assertStringContainsString('1. src/A [max_files]', $output);
        self::assertStringContainsString('2. src/B [max_files]', $output);
    }
}
