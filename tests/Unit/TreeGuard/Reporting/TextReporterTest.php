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
use Toolkit\TreeGuard\Reporting\TextReporter;
use Toolkit\TreeGuard\Reporting\ViolationFieldComparator;
use Toolkit\TreeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\TreeGuard\Reporting\TextReporter
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 * @uses \Toolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\TreeGuard\Reporting\ViolationSorter
 */
#[CoversClass(TextReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class TextReporterTest extends TestCase
{
    public function testReportFormatsPassingSummary(): void
    {
        $output = (new TextReporter())->report(
            new AnalysisResult(['src' => new DirectoryListing('src', ['A.php'], [])], []),
            new ReportConfig('text', ['path', 'rule']),
        );

        self::assertStringContainsString('TreeGuard passed. No violations found.', $output);
        self::assertStringContainsString('Summary: 1 directories, 1 files.', $output);
    }

    public function testReportFormatsViolationsWithOptionalCounts(): void
    {
        $output = (new TextReporter())->report(
            new AnalysisResult(
                ['src' => new DirectoryListing('src', ['A.php'], [])],
                [
                    new Violation('src', 'max_files', 'src', 2, 1, 'Too many files.'),
                    new Violation('src/notes.txt', 'disallowed_file', 'src/**', null, null, 'Not allowed.'),
                ],
            ),
            new ReportConfig('text', ['path', 'rule']),
        );

        self::assertStringContainsString('TreeGuard found 2 violations.', $output);
        self::assertStringContainsString("src [max_files]\n  Too many files.\n  Actual: 2, Limit: 1\n", $output);
        self::assertStringContainsString("src/notes.txt [disallowed_file]\n  Not allowed.\n", $output);
        self::assertStringNotContainsString('Actual: 0', $output);
    }
}
