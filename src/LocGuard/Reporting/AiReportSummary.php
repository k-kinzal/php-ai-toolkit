<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;

use function sprintf;

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
