<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;

/**
 * Renders one line per finding for a coding agent: severity, position, rule, symbol, message and fix.
 */
final class AiReporter implements Reporter
{
    /**
     * Creates a reporter, optionally with a shared summary.
     */
    public function __construct(private ?Summary $summary = null)
    {
    }

    /**
     * Returns compact lines with no colour, followed by the counts and the verdict.
     */
    public function render(Report $report, bool $decorated): string
    {
        $summary = $this->summary ?? new Summary();
        $lines = [];
        foreach ($report->findings as $finding) {
            $lines[] = sprintf('%s: %s:%d:%d [%s] %s confidence=%.2f %s Fix: %s', $finding->severity, $finding->path, $finding->line, $finding->column, $finding->rule, $finding->symbol, $finding->confidence, $finding->message, $finding->fix);
        }
        $lines[] = $summary->counts($report, ', ') . '.';
        $lines[] = $summary->verdict($report);

        return implode("\n", $lines) . "\n";
    }
}
