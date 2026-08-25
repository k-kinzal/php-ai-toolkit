<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Reporting;

use function sprintf;

use Toolkit\TreeGuard\Analysis\AnalysisResult;
use Toolkit\TreeGuard\Config\ReportConfig;

/**
 * Human-readable TreeGuard reporter.
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
            "Summary: %d directories, %d files.\n",
            $result->directoryCount(),
            $result->fileCount(),
        );

        if (!$result->hasViolations()) {
            return "TreeGuard passed. No violations found.\n" . $summary;
        }

        $output = sprintf("TreeGuard found %d violations.\n", $result->violationCount()) . $summary;
        foreach ($this->sorter->sort($result->violations, $config) as $violation) {
            $output .= sprintf("\n%s [%s]\n  %s\n", $violation->path, $violation->rule, $violation->message);
            if ($violation->actual !== null && $violation->limit !== null) {
                $output .= sprintf("  Actual: %d, Limit: %d\n", $violation->actual, $violation->limit);
            }
        }

        return $output;
    }
}
