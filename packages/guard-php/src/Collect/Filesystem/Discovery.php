<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\Matching\ScopeMatcher;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Input;
use Guard\Input\Scope;
use Guard\Input\Selection;

/**
 * Traverses the union of every registered selection in one shared directory queue.
 */
final class Discovery
{
    /**
     * Creates the Discovery with its declared dependencies.
     */
    public function __construct(private Snapshot $snapshot, private ?Scope $scope = null)
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
        $scope = $this->scope === null || $this->scope->include === [] || $inputs === [] ? null : new ScopeMatcher($root, $this->snapshot->inspect($root)->identity, $this->scope);
        foreach ($inputs as $id => $input) {
            $result = new QueryResult();
            $results[$id] = $result;
            if ($this->scope !== null && $this->scope->include === []) {
                continue;
            }
            try {
                (new SelectionRoots($this->snapshot, $scope))->seed($root, $id, $input->selection, $queue, $result);
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
            (new DirectoryTraversal($this->snapshot, $scope))->visit($root, $path, $entries, $routes, $inputs, $queue, $results);
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
