<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\FileRecord;
use Guard\Collect\Matching\SelectionFilter;
use Guard\Collect\Selection;

/**
 * Compiles literal pattern prefixes into roots so unrelated ancestors are never scanned.
 */
final class PatternRoots
{
    /**
     * Reuses path facts from all other selections.
     */
    public function __construct(private Snapshot $snapshot)
    {
    }
    /**
     * Selects exact files immediately and queues only the directory needing glob expansion.
     */
    public function seed(string $root, string $id, string $pattern, WalkQueue $queue, QueryResult $result, ?Selection $selection = null): void
    {
        $pattern = (new Path())->spelling($pattern);
        if (str_starts_with($pattern, '/')) {
            return;
        }
        $segments = explode('/', $pattern);
        $prefix = [];
        while ($segments !== [] && preg_match('/[*?\[]/', $segments[0]) !== 1) {
            $prefix[] = array_shift($segments);
        }
        $relative = implode('/', $prefix);
        if ($selection !== null && $selection->exclude !== [] && !(new SelectionFilter())->includes($selection, $relative)) {
            return;
        }
        $absolute = (new Path())->absolute($root, $relative === '' ? '.' : $relative);
        $entry = $this->snapshot->inspect($absolute);
        if ($segments === []) {
            if ($entry->file && ($selection === null || str_ends_with($relative, $selection->suffix))) {
                $result->addFile(new FileRecord($absolute, $relative, $entry));
            }
        } elseif ($entry->directory) {
            $queue->add($absolute, new Route($id, $segments, [0], []));
        }
    }
}
