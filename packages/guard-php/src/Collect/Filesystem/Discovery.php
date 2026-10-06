<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\Input;
use Guard\Collect\Selection;
use Guard\Execution\TargetPath;
use Guard\Policy\PolicyException;

/**
 * Traverses the union of every registered selection in one shared directory queue.
 */
final class Discovery
{
    /**
     * Creates the Discovery with its declared dependencies.
     */
    public function __construct(private Snapshot $snapshot)
    {
    }
    /** Builds all selections together; individual failures remain attached to their input.
     * @param array<string, Input> $inputs
     * @return array<string, QueryResult>
     */
    public function discover(string $root, array $inputs): array
    {
        $queue = new WalkQueue();
        $results = [];
        foreach ($inputs as $id => $input) {
            $result = new QueryResult();
            $results[$id] = $result;
            try {
                (new SelectionRoots($this->snapshot))->seed($root, $id, $input->selection, $queue, $result);
            } catch (PolicyException $error) {
                $result->fail($error);
            }
        }
        while (($next = $queue->next()) !== null) {
            [$path, $routes] = $next;
            $entries = $this->snapshot->entries($path);
            if ($entries === false) {
                foreach ($routes as $route) {
                    if ($inputs[$route->query]->selection->mode !== 'patterns') {
                        $results[$route->query]->fail(new PolicyException('Failed to read directory: ' . $path));
                    }
                }
                continue;
            }
            (new DirectoryTraversal($this->snapshot))->visit($root, $path, $entries, $routes, $inputs, $queue, $results);
        }
        foreach ($inputs as $id => $input) {
            $this->validate($root, $input->selection, $results[$id]);
        }
        return $results;
    }
    /**
     * Applies file access constraints to glob matches before any content is read.
     */
    public function validate(string $root, Selection $selection, QueryResult $result): void
    {
        if ($selection->mode !== 'patterns' || (!$selection->confined && $selection->forbiddenPath === null)) {
            return;
        }
        try {
            foreach ($result->files() as $file) {
                if ($selection->confined) {
                    (new TargetPath($this->snapshot))->resolve($root, $file->relativePath);
                }
                if ($selection->forbiddenPath !== null && $file->entry->identity === $this->snapshot->inspect($selection->forbiddenPath)->identity) {
                    throw new PolicyException($selection->forbiddenMessage);
                }
            }
        } catch (PolicyException $error) {
            $result->fail($error);
        }
    }
}
