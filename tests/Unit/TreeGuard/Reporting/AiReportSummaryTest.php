<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\AnalysisResult;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Reporting\AiReportSummary;

/**
 * @covers \Toolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Analysis\Violation
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
