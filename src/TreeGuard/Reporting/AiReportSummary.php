<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;

use function sprintf;

/**
 * Formats the summary block for AI TreeGuard reports.
 */
final class AiReportSummary
{
    /**
     * Returns the report summary block.
     */
    public function summary(AnalysisResult $result): string
    {
        return sprintf(
            "summary:\n- directories: %d\n- files: %d\n- violations: %d\n",
            $result->directoryCount(),
            $result->fileCount(),
            $result->violationCount(),
        );
    }
}
