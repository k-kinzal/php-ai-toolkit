<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\Matching\GlobMatcher;
use Guard\Collect\Matching\ScopeMatcher;
use Guard\Collect\Matching\SelectionFilter;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\Path;
use Guard\Input\Selection;

/**
 * Starts each request at the intersection of collector includes and requested paths.
 */
final class ScopedRoots
{
    /**
     * Shares metadata and the collector's scope across all input declarations.
     */
    public function __construct(private Snapshot $snapshot, private ScopeMatcher $scope)
    {
    }

    /**
     * Seeds exact files or intersected traversal roots without walking either range first.
     */
    public function seed(string $root, string $id, Selection $selection, WalkQueue $queue, QueryResult $result): void
    {
        foreach ($selection->paths as $path) {
            if ($selection->mode === 'files') {
                $this->exact($root, $path, $selection, $result);
                continue;
            }
            $requested = $this->scope->relative($path);
            $prefix = $selection->mode === 'patterns' ? $this->scope->prefix($requested) : $requested;
            foreach ($this->scope->roots($prefix) as $start) {
                $this->start($root, $id, $start, $requested, $selection, $queue, $result);
            }
        }
    }

    /**
     * Keeps explicit file requests inside the same scope, including missing files.
     * @throws PolicyException when an in-scope exact target violates its input constraints
     */
    public function exact(string $root, string $path, Selection $selection, QueryResult $result): void
    {
        $relative = $this->scope->relative($path);
        if (!$this->scope->contains($relative)) {
            return;
        }
        $absolute = (new Path())->absolute($root, $relative === '' ? '.' : $relative);
        $entry = $this->entry($root, $relative);
        if ($entry === null || !$this->scope->accepts($relative, $entry)) {
            return;
        }
        if ($selection->confined) {
            (new TargetPath($this->snapshot))->confine($root, $relative);
        }
        if ($selection->forbiddenPath !== null && $entry->identity === $this->snapshot->inspect($selection->forbiddenPath)->identity) {
            throw new PolicyException($selection->forbiddenMessage);
        }
        $result->addFile(new FileRecord($absolute, $path, $entry));
    }

    /**
     * Inspects only compatible roots and advances the input's original glob state to them.
     * @throws PolicyException when a requested recursive root is not a directory
     */
    public function start(string $root, string $id, string $relative, string $requested, Selection $selection, WalkQueue $queue, QueryResult $result): void
    {
        if ((!$this->scope->contains($relative) && !$this->scope->mayContain($relative)) || !(new SelectionFilter())->includes($selection, $relative)) {
            return;
        }
        $absolute = (new Path())->absolute($root, $relative === '' ? '.' : $relative);
        $entry = $this->entry($root, $relative);
        if ($entry === null || !$this->scope->accepts($relative, $entry)) {
            return;
        }
        if ($selection->mode === 'patterns') {
            $segments = explode('/', $requested);
            $positions = $this->positions($segments, $relative, $entry->directory);
            if ($entry->directory && $positions !== []) {
                $queue->add($absolute, new Route($id, $segments, $positions, []));
            } elseif ($entry->file && in_array(count($segments), $positions, true) && str_ends_with($relative, $selection->suffix)) {
                $result->addFile(new FileRecord($absolute, $relative, $entry));
            }
            return;
        }
        if (!$entry->directory && $requested === $relative) {
            throw new PolicyException(str_replace(['{path}', '{absolute}'], [$relative, $absolute], $selection->rootError));
        }
        if ($entry->directory) {
            $queue->add($absolute, new Route($id, [], [], []));
        } elseif ($entry->file && $selection->mode === 'directories') {
            $queue->add(dirname($absolute), new Route($id, [], [], []));
        } elseif ($entry->file && str_ends_with($relative, $selection->suffix)) {
            $result->addFile(new FileRecord($absolute, $relative, $entry));
        }
    }

    /**
     * Replays a known prefix without enumerating its ancestors.
     * @param list<string> $segments
     * @return list<int>
     */
    public function positions(array $segments, string $relative, bool $directory): array
    {
        $positions = [0];
        $parts = $relative === '' ? [] : explode('/', $relative);
        foreach ($parts as $index => $part) {
            $positions = (new GlobMatcher())->advance($segments, $positions, $part, $directory || $index < count($parts) - 1, false);
        }
        return $positions;
    }
    /**
     * Rejects symlinks in literal prefixes before inspecting or reading their descendants.
     */
    public function entry(string $root, string $relative): ?Entry
    {
        $absolute = rtrim($root, '/');
        foreach ($relative === '' ? [] : explode('/', $relative) as $part) {
            $absolute .= '/' . $part;
            if ($this->snapshot->inspect($absolute)->link) {
                return null;
            }
        }
        return $this->snapshot->inspect($absolute);
    }
}
