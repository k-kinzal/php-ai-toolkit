<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function strcmp;

use Toolkit\DocGuard\Analysis\Violation;

/**
 * Compares DocGuard violations by one configured field.
 *
 * Violations without a line sort before violations with one.
 */
final class ViolationFieldComparator
{
    /**
     * Returns the comparison result for the selected field: path, line, or rule.
     */
    public function compare(Violation $left, Violation $right, string $field): int
    {
        if ($field === 'path') {
            return strcmp($left->path, $right->path);
        }

        if ($field === 'rule') {
            return strcmp($left->rule, $right->rule);
        }

        return ($left->line ?? 0) <=> ($right->line ?? 0);
    }
}
