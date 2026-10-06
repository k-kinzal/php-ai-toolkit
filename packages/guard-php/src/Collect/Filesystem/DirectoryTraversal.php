<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\DirectoryListing;
use Guard\Collect\FileRecord;
use Guard\Collect\Input;
use Guard\Collect\Matching\GlobMatcher;
use Guard\Collect\Matching\ScopeMatcher;
use Guard\Collect\Matching\SelectionFilter;

/**
 * Dispatches each directory entry to interested selections without rereading its metadata.
 */
final class DirectoryTraversal
{
    /**
     * Shares the collector's directory and metadata snapshot.
     */
    public function __construct(private Snapshot $snapshot, private ?ScopeMatcher $scope = null)
    {
    }
    /**
     * Inspects entries once and routes them to all active requests before descending.
     * @param list<string> $entries
     * @param list<Route> $routes
     * @param array<string, Input> $inputs
     * @param array<string, QueryResult> $results
     */
    public function visit(string $root, string $path, array $entries, array $routes, array $inputs, WalkQueue $queue, array $results): void
    {
        $directories = [];
        $files = [];
        $paths = new Path();
        $relative = $paths->relative($root, $path);
        $identity = $this->snapshot->inspect($path)->identity;
        foreach ($entries as $name) {
            $absolute = $path . '/' . $name;
            $child = $paths->child($relative, $name);
            $entry = $this->entry($absolute, $child);
            if ($entry === null) {
                continue;
            }
            foreach ($routes as $route) {
                $selection = $inputs[$route->query]->selection;
                $result = $results[$route->query];
                $ancestors = array_merge($route->ancestors, [$identity]);
                $candidate = $selection->mode === 'descendants' ? $paths->relative($root, $absolute) : $child;
                if (!(new SelectionFilter())->includes($selection, $candidate)) {
                    continue;
                }
                if ($selection->mode === 'patterns') {
                    $positions = (new GlobMatcher())->advance($route->segments, $route->positions, $name, $entry->directory, $entry->link);
                    if ($entry->directory && $positions !== []) {
                        $queue->add($absolute, new Route($route->query, $route->segments, $positions, $ancestors));
                    } elseif ($entry->file && in_array(count($route->segments), $positions, true) && str_ends_with($child, $selection->suffix)) {
                        $result->addFile(new FileRecord($absolute, $child, $entry));
                    }
                } else {
                    if ($entry->directory) {
                        $directories[$route->query][$name] = $name;
                        if (($selection->mode === 'directories' || !$entry->link) && !in_array($entry->identity, $ancestors, true)) {
                            $queue->add($absolute, new Route($route->query, [], [], $ancestors));
                        }
                    } else {
                        $files[$route->query][$name] = $name;
                        if ($selection->mode !== 'directories' && str_ends_with($candidate, $selection->suffix)) {
                            $result->addFile(new FileRecord($absolute, $candidate, $entry));
                        }
                    }
                }
            }
        }
        $this->listings($relative, $routes, $inputs, $results, $files, $directories);
    }
    /**
     * Rejects unrelated paths before stat and resolved out-of-scope entries before traversal.
     */
    public function entry(string $absolute, string $relative): ?Entry
    {
        if ($this->scope !== null && !$this->scope->contains($relative) && !$this->scope->mayContain($relative)) {
            return null;
        }
        $entry = $this->snapshot->inspect($absolute);
        return $this->scope !== null && !$this->scope->accepts($relative, $entry) ? null : $entry;
    }
    /**
     * Completes directory queries from the children accumulated during traversal, without file reads.
     * @param list<Route> $routes
     * @param array<string, Input> $inputs
     * @param array<string, QueryResult> $results
     * @param array<string, array<string, string>> $files
     * @param array<string, array<string, string>> $directories
     */
    public function listings(string $relative, array $routes, array $inputs, array $results, array $files, array $directories): void
    {
        foreach ($routes as $route) {
            if ($inputs[$route->query]->selection->mode === 'directories') {
                $fileNames = array_values($files[$route->query] ?? []);
                $dirNames = array_values($directories[$route->query] ?? []);
                sort($fileNames);
                sort($dirNames);
                $results[$route->query]->addDirectory(new DirectoryListing($relative, $fileNames, $dirNames));
            }
        }
    }
}
