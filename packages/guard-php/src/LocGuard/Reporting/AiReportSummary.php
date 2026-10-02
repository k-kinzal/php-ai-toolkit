<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Reporting;

use function sprintf;

use Toolkit\LocGuard\Analysis\AnalysisResult;

/**
 * Formats the summary block for AI LocGuard reports.
 */
final class AiReportSummary
{
    /**
     * Returns the report summary block.
     */
    public function summary(AnalysisResult $result): string
    {
        return sprintf(
            "summary:\n- files: %d\n- physical_lines: %d\n- ncloc: %d\n- violations: %d\n",
            $result->fileCount(),
            $result->physicalLineCount(),
            $result->nonCommentLineCount(),
            $result->violationCount(),
        );
    }
}
