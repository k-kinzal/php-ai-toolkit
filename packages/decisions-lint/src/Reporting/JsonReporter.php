<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;
use JsonException;

/**
 * Renders the report as one JSON document for other tools to read.
 */
final class JsonReporter implements Reporter
{
    /**
     * Returns `findings`, `summary`, the run counts, `fail_on` and `success`.
     *
     * @throws JsonException when a finding holds text that cannot be encoded
     */
    public function render(Report $report, bool $decorated): string
    {
        $findings = [];
        foreach ($report->findings as $finding) {
            $findings[] = [
                'path' => $finding->path,
                'line' => $finding->line,
                'column' => $finding->column,
                'rule' => $finding->rule,
                'severity' => $finding->severity,
                'symbol' => $finding->symbol,
                'message' => $finding->message,
                'fix' => $finding->fix,
                'confidence' => $finding->confidence,
            ];
        }

        return json_encode([
            'findings' => $findings,
            'summary' => $report->counts(),
            'files' => $report->files,
            'targets' => $report->targets,
            'requests' => $report->requests,
            'models' => $report->models,
            'fail_on' => $report->failOn,
            'success' => !$report->failed(),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
