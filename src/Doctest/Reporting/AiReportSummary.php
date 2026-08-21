<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Execution\SuiteResult;

use function sprintf;

/**
 * Formats the summary block for AI doctest reports.
 *
 * @visibility namespace
 */
final class AiReportSummary
{
    /**
     * Returns the report summary block.
     */
    public function summary(SuiteResult $result): string
    {
        return sprintf(
            "summary:\n- files: %d\n- examples: %d\n- passed: %d\n- failed: %d\n- skipped: %d\n",
            $result->fileCount,
            $result->total(),
            $result->passedCount(),
            $result->failedCount(),
            $result->skippedCount(),
        );
    }
}
