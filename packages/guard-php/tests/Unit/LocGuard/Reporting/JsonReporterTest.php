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
use Toolkit\LocGuard\Reporting\JsonReporter;
use Toolkit\LocGuard\Reporting\ViolationFieldComparator;
use Toolkit\LocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\LocGuard\Reporting\JsonReporter
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Analysis\Violation
 * @uses \Toolkit\LocGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\LocGuard\Reporting\ViolationSorter
 */
#[CoversClass(JsonReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFieldComparator::class)]
#[UsesClass(ViolationSorter::class)]
final class JsonReporterTest extends TestCase
{
    public function testReportFormatsJsonPayload(): void
    {
        $output = (new JsonReporter())->report(
            new AnalysisResult(
                [new FileMetric('src/A.php', 10, 7)],
                [new Violation('src/A.php', 2, 'file_lines', 10, 5, 'Too long.', 'strict')],
            ),
            new ReportConfig('json', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('"status": "failed"', $output);
        self::assertStringContainsString('"physical_lines": 10', $output);
        self::assertStringContainsString('"rule": "file_lines"', $output);
        self::assertStringContainsString('"policy": "strict"', $output);
    }

    public function testReportAppliesConfiguredViolationOrder(): void
    {
        $output = (new JsonReporter())->report(
            new AnalysisResult(
                [new FileMetric('src/A.php', 10, 7)],
                [
                    new Violation('src/B.php', 1, 'method_lines', 11, 5, 'B'),
                    new Violation('src/A.php', 1, 'file_lines', 12, 5, 'A'),
                ],
            ),
            new ReportConfig('json', ['path', 'line', 'rule']),
        );

        self::assertLessThan(strpos($output, 'src/B.php'), strpos($output, 'src/A.php'));
    }
}
