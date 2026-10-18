<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Renders findings for a person at a terminal, grouped by file and coloured by severity.
 */
final class TextReporter implements Reporter
{
    /**
     * Creates a reporter, optionally with a shared summary.
     */
    public function __construct(private ?Summary $summary = null)
    {
    }

    /**
     * Returns the report grouped by file, each finding with its position, rule, symbol, message and fix.
     */
    public function render(Report $report, bool $decorated): string
    {
        $formatter = new OutputFormatter($decorated);
        $summary = $this->summary ?? new Summary();
        $lines = ['', '<options=bold>Decisions check</>', ''];
        $groups = [];
        foreach ($report->findings as $finding) {
            $groups[$finding->path][] = $finding;
        }
        foreach ($groups as $path => $findings) {
            $lines[] = '<options=bold>' . OutputFormatter::escape($path) . '</>';
            foreach ($findings as $finding) {
                $color = $this->color($finding->severity);
                $lines[] = sprintf('  <fg=%s>%d:%d %s</> <fg=cyan>%s</> %s <fg=gray>(confidence %.2f)</>', $color, $finding->line, $finding->column, $finding->severity, OutputFormatter::escape($finding->rule), OutputFormatter::escape($finding->symbol), $finding->confidence);
                $lines[] = '    ' . OutputFormatter::escape($finding->message);
                $lines[] = '    <fg=green>Fix:</> ' . OutputFormatter::escape($finding->fix);
            }
            $lines[] = '';
        }
        $lines[] = '<options=bold>' . OutputFormatter::escape($summary->counts($report, ' · ')) . '</>';
        $lines[] = ($report->failed() ? '<fg=red>' : '<fg=green>') . OutputFormatter::escape($summary->verdict($report)) . '</>';

        return $formatter->format(implode("\n", $lines) . "\n") ?? '';
    }

    /**
     * Returns the terminal colour of a severity.
     */
    public function color(string $severity): string
    {
        return match ($severity) {
            'error' => 'red',
            'warning' => 'yellow',
            default => 'blue',
        };
    }
}
