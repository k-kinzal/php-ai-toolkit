<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

/**
 * Merges all requests for a directory before it is visited, parents first.
 */
final class WalkQueue
{
    /** @var array<int, non-empty-array<string, list<Route>>> */
    private array $levels = [];
    /**
     * Adds a route to the directory's existing queue entry.
     */
    public function add(string $path, Route $route): void
    {
        $this->levels[substr_count($path, '/')][$path][] = $route;
    }
    /** Returns the next directory and all its requests, or null when finished.
     * @return ?array{string, list<Route>}
     */
    public function next(): ?array
    {
        if ($this->levels === []) {
            return null;
        }
        ksort($this->levels);
        $depth = array_key_first($this->levels);
        $path = array_key_first($this->levels[$depth]);
        $routes = [];
        foreach ($this->levels[$depth][$path] as $route) {
            $key = $route->query . "\0" . implode('/', $route->segments) . "\0" . implode(',', $route->positions);
            $routes[$key] = $route;
        }
        $level = $this->levels[$depth];
        unset($level[$path]);
        if ($level === []) {
            unset($this->levels[$depth]);
        } else {
            $this->levels[$depth] = $level;
        }
        return [$path, array_values($routes)];
    }
}
