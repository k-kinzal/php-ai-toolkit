<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Reporting\AiReportSummary;

/**
 * @covers \Toolkit\DocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Analysis\Violation
 */
#[CoversClass(AiReportSummary::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(Violation::class)]
final class AiReportSummaryTest extends TestCase
{
    public function testSummaryListsCounts(): void
    {
        $result = new AnalysisResult(3, 25, [new Violation('README.md', null, 'missing_document', null, null, 'Missing.')]);

        self::assertSame("summary:\n- documents: 3\n- headings: 25\n- violations: 1\n", (new AiReportSummary())->summary($result));
    }
}
