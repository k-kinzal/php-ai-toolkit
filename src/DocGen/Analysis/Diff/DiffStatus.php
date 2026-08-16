<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

/**
 * The four states one documented element can have in a diff.
 *
 * A plain constant holder is used instead of a native enum to keep the
 * toolkit compatible with PHP 8.0.
 */
final class DiffStatus
{
    /**
     * State of an element that exists only in the head revision.
     */
    public const ADDED = 'added';

    /**
     * State of an element that exists only in the base revision.
     */
    public const REMOVED = 'removed';

    /**
     * State of an element that exists in both revisions but differs.
     */
    public const MODIFIED = 'modified';

    /**
     * State of an element that is identical in both revisions.
     */
    public const SAME = 'same';

    /**
     * Combines the states of the parts of one element into its own state.
     *
     * Parts that disagree make the container modified rather than added or
     * removed, so a container is only ever called new or gone when every
     * part of it agrees, and a container that holds one change is never
     * hidden as unchanged.
     *
     * @param list<string> $statuses
     */
    public function combine(array $statuses): string
    {
        if ($statuses === []) {
            return self::SAME;
        }

        $combined = $statuses[0];
        foreach ($statuses as $status) {
            if ($status !== $combined) {
                return self::MODIFIED;
            }
        }

        return $combined;
    }
}
