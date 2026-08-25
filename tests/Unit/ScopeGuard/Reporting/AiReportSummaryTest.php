<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult
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
