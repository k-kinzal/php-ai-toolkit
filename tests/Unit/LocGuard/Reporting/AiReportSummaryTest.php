<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Reporting\AiReportSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiReportSummary::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(Violation::class)]
final class AiReportSummaryTest extends TestCase
{
    public function testSummaryReturnsCounts(): void
    {
        $summary = (new AiReportSummary())->summary(new AnalysisResult(
            [new FileMetric('src/A.php', 10, 7)],
            [new Violation('src/A.php', 1, 'file_lines', 10, 5, 'Large.')],
        ));

        self::assertStringContainsString('- files: 1', $summary);
        self::assertStringContainsString('- violations: 1', $summary);
    }
}
