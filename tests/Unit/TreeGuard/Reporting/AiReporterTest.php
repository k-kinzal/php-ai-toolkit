<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\AnalysisResult;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Reporting\AiReporter;
use Toolkit\TreeGuard\Reporting\AiReportGuidance;
use Toolkit\TreeGuard\Reporting\AiReportSummary;
use Toolkit\TreeGuard\Reporting\AiViolationAction;
use Toolkit\TreeGuard\Reporting\AiViolationFormatter;
use Toolkit\TreeGuard\Reporting\ViolationFieldComparator;
use Toolkit\TreeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\TreeGuard\Reporting\AiReporter
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\TreeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 * @uses \Toolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\TreeGuard\Reporting\ViolationSorter
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
