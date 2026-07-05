<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;

use function strcmp;

/**
 * Compares LocGuard violations by one configured field.
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
            return $left->actual <=> $right->actual;
        }

        if ($field === 'limit') {
            return $left->limit <=> $right->limit;
        }

        return $left->line <=> $right->line;
    }
}
