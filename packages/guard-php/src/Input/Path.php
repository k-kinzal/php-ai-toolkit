<?php

declare(strict_types=1);

namespace Guard\Input;

/**
 * Resolves and normalizes paths for shared filesystem selection.
 */
final class Path
{
    /**
     * Resolves a path without changing its project-relative spelling.
     */
    public function absolute(string $root, string $path): string
    {
        return rtrim(str_starts_with($path, '/') ? $path : $root . ($path === '.' ? '' : '/' . $path), '/');
    }
    /**
     * Removes redundant separators and dot segments for queue deduplication.
     */
    public function normalize(string $path): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..' && $parts !== [] && end($parts) !== '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }
        $result = (str_starts_with($path, '/') ? '/' : '') . implode('/', $parts);
        return $result === '' ? '.' : $result;
    }
    /**
     * Returns a root-relative path when possible.
     */
    public function relative(string $root, string $path): string
    {
        $prefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        return rtrim($path, '/') === rtrim($prefix, '/') ? '.' : (str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path);
    }
    /**
     * Joins a root-relative directory and child name.
     */
    public function child(string $directory, string $name): string
    {
        return $directory === '.' ? $name : $directory . '/' . $name;
    }
    /**
     * Returns the prefix of all descendants of a root-relative directory.
     */
    public function descendantPrefix(string $directory): string
    {
        return $directory === '.' ? '' : $directory . '/';
    }
    /**
     * Retains the established spelling of configured document paths.
     */
    public function spelling(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        while (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }
        $path = $path === '/' ? $path : rtrim($path, '/');
        return $path === '' ? '.' : $path;
    }
}
