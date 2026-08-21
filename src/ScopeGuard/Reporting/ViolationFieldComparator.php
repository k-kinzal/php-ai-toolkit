<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\Violation;

use function strcmp;

/**
 * Compares ScopeGuard violations by one configured field.
 *
 * @visibility namespace
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

        if ($field === 'line') {
            return $left->line <=> $right->line;
        }

        if ($field === 'rule') {
            return strcmp($left->rule, $right->rule);
        }

        return strcmp($left->symbol, $right->symbol);
    }
}
