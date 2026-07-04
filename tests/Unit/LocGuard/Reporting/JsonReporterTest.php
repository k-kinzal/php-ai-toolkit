<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Reporting\JsonReporter;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonReporter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationSorter::class)]
final class JsonReporterTest extends TestCase
{
    public function testReportFormatsJsonPayload(): void
    {
        $output = (new JsonReporter())->report(
            new AnalysisResult(
                [new FileMetric('src/A.php', 10, 7)],
                [new Violation('src/A.php', 2, 'file_lines', 10, 5, 'Too long.')],
            ),
            new ReportConfig('json', ['path', 'line', 'rule']),
        );

        self::assertStringContainsString('"status": "failed"', $output);
        self::assertStringContainsString('"physical_lines": 10', $output);
        self::assertStringContainsString('"rule": "file_lines"', $output);
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
