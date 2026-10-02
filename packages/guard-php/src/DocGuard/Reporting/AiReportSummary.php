<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function sprintf;

use Toolkit\DocGuard\Analysis\AnalysisResult;

/**
 * Formats the summary block for AI DocGuard reports.
 */
final class AiReportSummary
{
    /**
     * Returns the report summary block.
     */
    public function summary(AnalysisResult $result): string
    {
        return sprintf(
            "summary:\n- documents: %d\n- headings: %d\n- violations: %d\n",
            $result->documents,
            $result->headings,
            $result->violationCount(),
        );
    }
}
