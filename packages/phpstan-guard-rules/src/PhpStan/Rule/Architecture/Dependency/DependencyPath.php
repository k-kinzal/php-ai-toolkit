<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture\Dependency;

/**
 * Matches dependency paths against globs anchored at one project root.
 */
final class DependencyPath
{
    private string $root;

    private string $realRoot;

    /**
     * Creates the path boundary using an absolute project directory.
     */
    public function __construct(string $projectRoot)
    {
        $this->root = rtrim($this->normalize($projectRoot), '/');
        $real = realpath($projectRoot);
        $this->realRoot = $real === false ? $this->root : rtrim($this->normalize($real), '/');
    }

    /**
     * Returns project-relative spellings, including the destination of symlinks.
     *
     * @return list<string>
     */
    public function relativePaths(string $path): array
    {
        if (str_contains($path, "\0") || (str_contains($path, '://') && !str_starts_with($path, 'file:///'))) {
            return [];
        }
        $absolute = $this->localFile($path) ?? $this->root . '/' . $path;
        $paths = [$absolute];
        $real = realpath($absolute);
        if ($real !== false) {
            $paths[] = $real;
        }

        $relative = [];
        foreach ($paths as $candidate) {
            $normalized = $this->normalize($candidate);
            foreach ([$this->root, $this->realRoot] as $root) {
                if (str_starts_with($normalized, $root . '/')) {
                    $relative[] = substr($normalized, strlen($root) + 1);
                }
            }
        }

        return array_values(array_unique($relative));
    }

    /**
     * Tests root-relative globs; a double star crosses directory boundaries.
     *
     * @param list<string> $patterns
     */
    public function matches(string $path, array $patterns): bool
    {
        foreach ($this->relativePaths($path) as $relative) {
            foreach ($patterns as $pattern) {
                $quoted = preg_quote(str_replace('\\', '/', $pattern), '~');
                $regex = strtr($quoted, ['\*\*/' => '(?:.*/)?', '\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]']);
                if (preg_match('~^' . $regex . '$~D', $relative) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Formats a dependency path without an environment-specific root prefix.
     */
    public function display(string $path): string
    {
        return $this->relativePaths($path)[0] ?? $this->normalize($path);
    }

    /**
     * Accepts absolute local paths and local file URLs, without guessing runtime cwd.
     */
    public function localFile(string $path): ?string
    {
        if (str_starts_with($path, 'file:///')) {
            $path = substr($path, 7);
        }
        if (str_contains($path, "\0") || str_contains($path, '://')) {
            return null;
        }

        return str_starts_with($path, '/') || preg_match('~^[A-Za-z]:[/\\\\]~', $path) === 1 ? $path : null;
    }

    /**
     * Normalizes separators and dot segments without requiring the target to exist.
     */
    public function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return (str_starts_with($path, '/') ? '/' : '') . implode('/', $parts);
    }
}
