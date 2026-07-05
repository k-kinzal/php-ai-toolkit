<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

use function usort;

/**
 * Sorts violations according to LocGuard report ordering configuration.
 */
final class ViolationSorter
{
    /** @readonly */
    private ViolationFieldComparator $fieldComparator;

    /**
     * Creates a sorter from field comparison behavior.
     */
    public function __construct(?ViolationFieldComparator $fieldComparator = null)
    {
        $this->fieldComparator = $fieldComparator ?? new ViolationFieldComparator();
    }

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
                $comparison = $this->fieldComparator->compare($left, $right, $field);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return 0;
        });

        return $violations;
    }
}
