<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function sprintf;

use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * Human-readable DocGuard reporter.
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
        $summary = sprintf("Summary: %d documents, %d headings.\n", $result->documents, $result->headings);

        if (!$result->hasViolations()) {
            return "DocGuard passed. No violations found.\n" . $summary;
        }

        $output = sprintf("DocGuard found %d violations.\n", $result->violationCount()) . $summary;
        foreach ($this->sorter->sort($result->violations, $config) as $violation) {
            $location = $violation->line === null ? $violation->path : sprintf('%s:%d', $violation->path, $violation->line);
            $output .= sprintf("\n%s [%s]\n  %s\n", $location, $violation->rule, $violation->message);
        }

        return $output;
    }
}
