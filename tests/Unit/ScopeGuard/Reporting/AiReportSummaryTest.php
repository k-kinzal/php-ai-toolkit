<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Reporting\AiReportSummary;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\ScopeGuard\Analysis\AnalysisResult
 */
#[CoversClass(AiReportSummary::class)]
#[UsesClass(AnalysisResult::class)]
final class AiReportSummaryTest extends TestCase
{
    public function testSummaryListsEveryCountedField(): void
    {
        self::assertSame(
            "summary:\n- files: 4\n- scoped_declarations: 2\n- references: 9\n- violations: 0\n",
            (new AiReportSummary())->summary(new AnalysisResult(4, 2, 9, []))
        );
    }
}
