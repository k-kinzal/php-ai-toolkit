<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\RunResult;

use function strcmp;
use function usort;

/**
 * Sorts run results according to doctest report ordering configuration.
 *
 * The order is decided here rather than left to discovery order, so the same
 * sources produce the same report on every platform and PHP version.
 *
 * @visibility namespace
 */
final class ResultSorter
{
    /**
     * Returns results sorted by the configured fields.
     *
     * @param list<RunResult> $results
     * @return list<RunResult>
     */
    public function sort(array $results, ReportConfig $config): array
    {
        usort($results, function (RunResult $left, RunResult $right) use ($config): int {
            foreach ($config->orderBy as $field) {
                $comparison = $this->compare($left, $right, $field);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return 0;
        });

        return $results;
    }

    /**
     * Returns the comparison of two results on one field.
     */
    public function compare(RunResult $left, RunResult $right, string $field): int
    {
        if ($field === 'path') {
            return strcmp($left->example->target->reportPath(), $right->example->target->reportPath());
        }

        if ($field === 'line') {
            return $left->example->line <=> $right->example->line;
        }

        return strcmp($left->example->id(), $right->example->id());
    }
}
