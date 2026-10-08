<?php

declare(strict_types=1);

namespace Guard\Collect\Matching;

use Guard\Input\Entry;
use Guard\Input\Path;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Scope;

/**
 * Intersects input requests with the collector boundary before filesystem operations.
 */
final class ScopeMatcher
{
    /** @var list<string> */
    private array $patterns = [];
    /** @var list<string> */
    private array $prefixes = [];
    /** @var list<string> */
    private array $excluded = [];
    /** @var array<string, bool> */
    private array $membership = [];
    /** @var array<string, bool> */
    private array $descendants = [];

    /**
     * Compiles directory includes into descendant globs once per collection.
     */
    public function __construct(private string $root, private string $physicalRoot, Scope $scope)
    {
        foreach ($scope->include as $include) {
            $pattern = $this->relative($include);
            $this->prefixes[] = $this->prefix($pattern);
            $this->patterns[] = $pattern;
            if (preg_match('/[*?\[]/', $pattern) !== 1) {
                $this->patterns[] = $pattern === '' ? '**' : $pattern . '/**';
            }
        }
        foreach ($scope->exclude as $exclude) {
            $this->excluded[] = $this->relative($exclude);
        }
        $this->prefixes = array_values(array_unique($this->prefixes));
    }

    /**
     * Normalizes equivalent spellings before matching the project boundary.
     */
    public function relative(string $path): string
    {
        $paths = new Path();
        $relative = $paths->relative($paths->normalize($this->root), $paths->normalize($paths->absolute($this->root, $path)));
        return $relative === '.' ? '' : $relative;
    }

    /**
     * Reports whether a file or an explicitly included directory belongs to the scope.
     */
    public function contains(string $path): bool
    {
        $path = $this->relative($path);
        if (isset($this->membership[$path])) {
            return $this->membership[$path];
        }
        if ($this->excludes($path)) {
            return $this->membership[$path] = false;
        }
        foreach ($this->patterns as $pattern) {
            if ((new PathPatternMatcher())->matches($pattern, $path)) {
                return $this->membership[$path] = true;
            }
        }
        return $this->membership[$path] = false;
    }

    /**
     * Rejects subtrees which cannot contain any included path, before directory enumeration.
     */
    public function mayContain(string $path): bool
    {
        $path = $this->relative($path);
        if (isset($this->descendants[$path])) {
            return $this->descendants[$path];
        }
        if ($this->excludes($path)) {
            return $this->descendants[$path] = false;
        }
        foreach ($this->patterns as $pattern) {
            if ((new PathPatternMatcher())->canMatchBelow($pattern, $path)) {
                return $this->descendants[$path] = true;
            }
        }
        return $this->descendants[$path] = false;
    }

    /**
     * Checks exclusions against a path and each of its ancestors.
     */
    public function excludes(string $path): bool
    {
        if (str_starts_with($path, '/') || $path === '..' || str_starts_with($path, '../')) {
            return true;
        }
        $parts = $path === '' ? [] : explode('/', $path);
        do {
            foreach ($this->excluded as $pattern) {
                if ((new PathPatternMatcher())->matches($pattern, implode('/', $parts))) {
                    return true;
                }
            }
            $last = array_pop($parts);
        } while ($last !== null);
        return false;
    }

    /**
     * Finds the deepest compatible literal roots instead of enumerating unrelated ancestors.
     * @return list<string>
     */
    public function roots(string $prefix): array
    {
        $prefix = $this->relative($prefix);
        $roots = [];
        foreach ($this->prefixes as $included) {
            if ($prefix === $included || $included === '' || str_starts_with($prefix, $included . '/')) {
                $roots[] = $prefix;
            } elseif ($prefix === '' || str_starts_with($included, $prefix . '/')) {
                $roots[] = $included;
            }
        }
        return array_values(array_unique($roots));
    }

    /**
     * Extracts the literal prefix of a glob, without stat calls.
     */
    public function prefix(string $pattern): string
    {
        $prefix = [];
        foreach (explode('/', $pattern) as $part) {
            if (preg_match('/[*?\[]/', $part) === 1) {
                break;
            }
            $prefix[] = $part;
        }
        return implode('/', $prefix);
    }

    /**
     * Keeps resolved paths within the same boundary and does not follow symlinks.
     */
    public function accepts(string $relative, Entry $entry): bool
    {
        if ($entry->link) {
            return false;
        }
        if (!$entry->file && !$entry->directory) {
            return $this->contains($relative);
        }
        $physical = (new Path())->relative($this->physicalRoot, $entry->identity);
        $physical = $physical === '.' ? '' : $physical;
        if ($entry->directory) {
            return ($this->mayContain($relative) || $this->contains($relative)) && ($this->mayContain($physical) || $this->contains($physical));
        }
        return $this->contains($relative) && $this->contains($physical);
    }
}
