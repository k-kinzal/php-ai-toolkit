<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;

/**
 * Renders the report as GitHub Actions workflow commands, so findings appear as annotations on the changed lines.
 */
final class GithubReporter implements Reporter
{
    /**
     * Creates a reporter, optionally with a shared summary.
     */
    public function __construct(private ?Summary $summary = null)
    {
    }

    /**
     * Returns one `::error`, `::warning` or `::notice` command per finding, then the counts and the verdict as plain lines.
     */
    public function render(Report $report, bool $decorated): string
    {
        $summary = $this->summary ?? new Summary();
        $lines = [];
        foreach ($report->findings as $finding) {
            $command = match ($finding->severity) {
                'error' => 'error',
                'warning' => 'warning',
                default => 'notice',
            };
            $lines[] = sprintf('::%s file=%s,line=%d,col=%d,title=%s::%s', $command, $this->property($finding->path), $finding->line, $finding->column, $this->property($finding->rule), $this->data($finding->message . ' Fix: ' . $finding->fix));
        }
        $lines[] = $summary->counts($report, ', ') . '.';
        $lines[] = $summary->verdict($report);

        return implode("\n", $lines) . "\n";
    }

    /**
     * Escapes a command property value.
     */
    public function property(string $value): string
    {
        return strtr($value, ['%' => '%25', "\r" => '%0D', "\n" => '%0A', ':' => '%3A', ',' => '%2C']);
    }

    /**
     * Escapes the command message.
     */
    public function data(string $value): string
    {
        return strtr($value, ['%' => '%25', "\r" => '%0D', "\n" => '%0A']);
    }
}
