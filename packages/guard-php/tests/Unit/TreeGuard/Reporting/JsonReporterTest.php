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
use Toolkit\TreeGuard\Reporting\JsonReporter;
use Toolkit\TreeGuard\Reporting\ViolationFieldComparator;
use Toolkit\TreeGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\TreeGuard\Reporting\JsonReporter
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 * @uses \Toolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\TreeGuard\Reporting\ViolationSorter
 */
#[CoversClass(JsonReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class JsonReporterTest extends TestCase
{
    public function testReportFormatsPassingStatus(): void
    {
        $output = (new JsonReporter())->report(
            new AnalysisResult(['src' => new DirectoryListing('src', ['A.php'], [])], []),
            new ReportConfig('json', ['path', 'rule']),
        );

        self::assertStringContainsString('"status": "passed"', $output);
        self::assertStringContainsString('"directories": 1', $output);
        self::assertStringContainsString('"files": 1', $output);
        self::assertStringContainsString('"violations": []', $output);
    }

    public function testReportFormatsViolationsWithNullCounts(): void
    {
        $output = (new JsonReporter())->report(
            new AnalysisResult(
                ['src' => new DirectoryListing('src', ['A.php'], [])],
                [new Violation('src/notes.txt', 'disallowed_file', 'src/**', null, null, 'Not allowed.')],
            ),
            new ReportConfig('json', ['path', 'rule']),
        );

        self::assertStringContainsString('"status": "failed"', $output);
        self::assertStringContainsString('"path": "src/notes.txt"', $output);
        self::assertStringContainsString('"rule": "disallowed_file"', $output);
        self::assertStringContainsString('"pattern": "src/**"', $output);
        self::assertStringContainsString('"actual": null', $output);
        self::assertStringContainsString('"limit": null', $output);
    }
}
