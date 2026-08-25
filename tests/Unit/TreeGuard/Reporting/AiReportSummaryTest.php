<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Reporting\AiReportSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \PhpAiToolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(AiReportSummary::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(Violation::class)]
final class AiReportSummaryTest extends TestCase
{
    public function testSummaryFormatsCounts(): void
    {
        $result = new AnalysisResult(
            [
                'src' => new DirectoryListing('src', ['Root.php'], ['A']),
                'src/A' => new DirectoryListing('src/A', ['One.php', 'Two.php'], []),
            ],
            [new Violation('src', 'max_files', 'src', 3, 1, 'Too many.')],
        );

        self::assertSame("summary:\n- directories: 2\n- files: 3\n- violations: 1\n", (new AiReportSummary())->summary($result));
    }
}
