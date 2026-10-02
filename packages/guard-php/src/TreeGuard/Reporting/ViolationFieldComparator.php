<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Reporting;

use function strcmp;

use Toolkit\TreeGuard\Analysis\Violation;

/**
 * Compares TreeGuard violations by one configured field.
 *
 * Null actual and limit values sort before any integer value.
 */
final class ViolationFieldComparator
{
    /**
     * Returns comparison result for the selected field.
     */
    public function compare(Violation $left, Violation $right, string $field): int
    {
        if ($field === 'path') {
            return strcmp($left->path, $right->path);
        }

        if ($field === 'rule') {
            return strcmp($left->rule, $right->rule);
        }

        if ($field === 'actual') {
            return $this->compareNullableInt($left->actual, $right->actual);
        }

        return $this->compareNullableInt($left->limit, $right->limit);
    }

    /**
     * Returns comparison result for nullable integers with null ordered first.
     */
    public function compareNullableInt(?int $left, ?int $right): int
    {
        if ($left === null && $right === null) {
            return 0;
        }

        if ($left === null) {
            return -1;
        }

        if ($right === null) {
            return 1;
        }

        return $left <=> $right;
    }
}
