<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

use function sprintf;

/**
 * Human-readable LocGuard reporter.
 */
final class TextReporter implements Reporter
{
    /** @readonly */
    private ViolationSorter $sorter;

    /**
     * Creates a text reporter with violation ordering support.
     */
    public function __construct(?ViolationSorter $sorter = null)
    {
        $this->sorter = $sorter ?? new ViolationSorter();
    }

    /**
     * Formats a concise human-readable report.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string
    {
        $summary = sprintf(
            "Summary: %d files, %d physical lines, %d NCLOC.\n",
            $result->fileCount(),
            $result->physicalLineCount(),
            $result->nonCommentLineCount(),
        );

        if (!$result->hasViolations()) {
            return "LocGuard passed. No violations found.\n" . $summary;
        }

        $output = sprintf("LocGuard found %d violations.\n", $result->violationCount()) . $summary;
        foreach ($this->sorter->sort($result->violations, $config) as $violation) {
            $output .= sprintf(
                "\n%s:%d [%s]\n  %s\n  Actual: %d, Limit: %d\n",
                $violation->path,
                $violation->line,
                $violation->rule,
                $violation->message,
                $violation->actual,
                $violation->limit,
            );
        }

        return $output;
    }
}
