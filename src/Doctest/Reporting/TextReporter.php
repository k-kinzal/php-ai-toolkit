<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\SuiteResult;

use function sprintf;

/**
 * Human-readable doctest reporter.
 *
 * @visibility namespace
 */
final class TextReporter implements Reporter
{
    /** @readonly */
    private ResultSorter $sorter;

    /**
     * Creates a text reporter with result ordering support.
     */
    public function __construct(?ResultSorter $sorter = null)
    {
        $this->sorter = $sorter ?? new ResultSorter();
    }

    /**
     * Formats a concise human-readable report.
     */
    public function report(SuiteResult $result, ReportConfig $config): string
    {
        $summary = sprintf(
            "Summary: %d files, %d examples, %d passed, %d failed, %d skipped.\n",
            $result->fileCount,
            $result->total(),
            $result->passedCount(),
            $result->failedCount(),
            $result->skippedCount(),
        );

        if (!$result->hasFailures()) {
            return "Doctest passed. Every documented example holds.\n" . $summary;
        }

        $output = sprintf("Doctest found %d failing examples.\n", $result->failedCount()) . $summary;
        foreach ($this->sorter->sort($result->failed(), $config) as $failed) {
            $output .= sprintf(
                "\n%s:%d %s\n%s\n",
                $failed->example->target->reportPath(),
                $failed->example->line,
                $failed->example->id(),
                $failed->errorMessage(),
            );
        }

        return $output;
    }
}
