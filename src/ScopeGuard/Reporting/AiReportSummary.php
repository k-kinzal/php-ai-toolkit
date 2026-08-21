<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;

use function sprintf;

/**
 * Formats the summary block for AI ScopeGuard reports.
 *
 * @visibility namespace
 */
final class AiReportSummary
{
    /**
     * Returns the report summary block.
     */
    public function summary(AnalysisResult $result): string
    {
        return sprintf(
            "summary:\n- files: %d\n- scoped_declarations: %d\n- references: %d\n- violations: %d\n",
            $result->fileCount,
            $result->scopedDeclarationCount,
            $result->referenceCount,
            $result->violationCount(),
        );
    }
}
