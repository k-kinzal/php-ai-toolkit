<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function array_key_exists;
use function count;
use function implode;
use function sprintf;

/**
 * Counts and summarizes PHPUnit issue types.
 */
final class TestIssueSummary
{
    /**
     * Counts issues by type.
     *
     * @param list<TestIssue> $issues issues to count
     * @return array<string, int> type to count mapping
     */
    public function countByType(array $issues): array
    {
        $counts = [];
        foreach ($issues as $issue) {
            if (!array_key_exists($issue->type, $counts)) {
                $counts[$issue->type] = 0;
            }
            $counts[$issue->type]++;
        }

        return $counts;
    }

    /**
     * Builds a human-readable count summary string.
     *
     * @param array<string, int> $counts type to count mapping
     */
    public function buildCountSummary(array $counts, int $total): string
    {
        $parts = [];

        $failedCount = $counts[TestIssue::TYPE_FAILED] ?? 0;
        if ($failedCount > 0) {
            $parts[] = sprintf('%d %s', $failedCount, $failedCount === 1 ? 'failure' : 'failures');
        }

        $errorCount = $counts[TestIssue::TYPE_ERROR] ?? 0;
        if ($errorCount > 0) {
            $parts[] = sprintf('%d %s', $errorCount, $errorCount === 1 ? 'error' : 'errors');
        }

        $riskyCount = $counts[TestIssue::TYPE_RISKY] ?? 0;
        if ($riskyCount > 0) {
            $parts[] = sprintf('%d risky', $riskyCount);
        }

        $skippedCount = $counts[TestIssue::TYPE_SKIPPED] ?? 0;
        if ($skippedCount > 0) {
            $parts[] = sprintf('%d skipped', $skippedCount);
        }

        if (count($parts) === 0) {
            return sprintf('%d %s', $total, $total === 1 ? 'issue' : 'issues');
        }

        return implode(', ', $parts);
    }
}
