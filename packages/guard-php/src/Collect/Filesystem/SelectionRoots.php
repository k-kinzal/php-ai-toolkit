<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\FileRecord;
use Guard\Collect\Matching\ScopeMatcher;
use Guard\Collect\Selection;
use Guard\Execution\TargetPath;
use Guard\Policy\PolicyException;

/**
 * Resolves selection roots and exact files before any directory traversal.
 */
final class SelectionRoots
{
    /**
     * Shares path metadata with the collector traversal.
     */
    public function __construct(private Snapshot $snapshot, private ?ScopeMatcher $scope = null)
    {
    }
    /** Queues directory roots and records exact file requests without directory scans.
     * @throws PolicyException
     */
    public function seed(string $root, string $id, Selection $selection, WalkQueue $queue, QueryResult $result): void
    {
        if ($this->scope !== null) {
            (new ScopedRoots($this->snapshot, $this->scope))->seed($root, $id, $selection, $queue, $result);
            return;
        }
        $paths = new Path();
        foreach ($selection->paths as $path) {
            if ($selection->mode === 'patterns') {
                (new PatternRoots($this->snapshot))->seed($root, $id, $path, $queue, $result, $selection);
                continue;
            }
            $absolute = !$selection->confined ? $paths->absolute($root, $path)
                : ($selection->mode === 'files' ? (new TargetPath($this->snapshot))->confine($root, $path) : (new TargetPath($this->snapshot))->resolve($root, $path));
            $entry = $this->snapshot->inspect($absolute);
            if ($selection->forbiddenPath !== null && $entry->identity === $this->snapshot->inspect($selection->forbiddenPath)->identity) {
                throw new PolicyException($selection->forbiddenMessage);
            }
            if ($selection->mode === 'files') {
                $result->addFile(new FileRecord($absolute, $path, $entry));
                continue;
            }
            if (!$entry->directory) {
                throw new PolicyException(str_replace(['{path}', '{absolute}'], [$path, $absolute], $selection->rootError));
            }
            $queue->add($absolute, new Route($id, [], [], []));
        }
    }
}
