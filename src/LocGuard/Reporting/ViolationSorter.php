<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

use function strcmp;
use function usort;

/**
 * Sorts violations according to LocGuard report ordering configuration.
 */
final class ViolationSorter
{
    /**
     * Returns violations sorted by configured fields.
     *
     * @param list<Violation> $violations
     * @return list<Violation>
     */
    public function sort(array $violations, ReportConfig $config): array
    {
        usort($violations, function (Violation $left, Violation $right) use ($config): int {
            foreach ($config->orderBy as $field) {
                $comparison = $this->compareField($left, $right, $field);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return 0;
        });

        return $violations;
    }

    private function compareField(Violation $left, Violation $right, string $field): int
    {
        if ($field === 'path') {
            return strcmp($left->path, $right->path);
        }

        if ($field === 'rule') {
            return strcmp($left->rule, $right->rule);
        }

        if ($field === 'actual') {
            return $left->actual <=> $right->actual;
        }

        if ($field === 'limit') {
            return $left->limit <=> $right->limit;
        }

        return $left->line <=> $right->line;
    }
}
