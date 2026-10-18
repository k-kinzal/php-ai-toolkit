<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Lint\Report;

/**
 * The closing lines every text-like format shares: the counts and whether the run passed.
 */
final class Summary
{
    /**
     * Returns the counts joined by a separator, such as "1 error, 2 warnings, 0 infos, 2 files with findings, 14 targets, 9 requests to jev-1.13.0".
     */
    public function counts(Report $report, string $separator): string
    {
        $counts = $report->counts();
        $files = $report->filesWithFindings();
        $parts = [
            $this->plural($counts['error'], 'error', 'errors'),
            $this->plural($counts['warning'], 'warning', 'warnings'),
            $this->plural($counts['info'], 'info', 'infos'),
            $this->plural($files, 'file', 'files') . ' with findings',
            $this->plural($report->targets, 'target', 'targets'),
            $this->plural($report->requests, 'request', 'requests') . ($report->models === [] ? '' : ' to ' . implode(', ', $report->models)),
        ];

        return implode($separator, $parts);
    }

    /**
     * Returns the verdict line of a run.
     */
    public function verdict(Report $report): string
    {
        return $report->failed()
            ? sprintf('Decisions check failed: a finding is at least %s and fail_on is %s.', $report->failOn, $report->failOn)
            : 'Decisions check passed.';
    }

    /**
     * Joins a count with its singular or plural noun.
     */
    public function plural(int $count, string $singular, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }
}
