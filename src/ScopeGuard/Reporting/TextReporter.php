<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Reporting;

use function sprintf;

use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Config\ReportConfig;

/**
 * Human-readable ScopeGuard reporter.
 *
 * @visibility namespace
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
            "Summary: %d files, %d scoped declarations, %d references.\n",
            $result->fileCount,
            $result->scopedDeclarationCount,
            $result->referenceCount,
        );

        if (!$result->hasViolations()) {
            return "ScopeGuard passed. No violations found.\n" . $summary;
        }

        $output = sprintf("ScopeGuard found %d violations.\n", $result->violationCount()) . $summary;
        foreach ($this->sorter->sort($result->violations, $config) as $violation) {
            $output .= sprintf("\n%s:%d [%s]\n  %s\n", $violation->path, $violation->line, $violation->rule, $violation->message);
        }

        return $output;
    }
}
