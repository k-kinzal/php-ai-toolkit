<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Filesystem;

use function rtrim;
use function str_replace;
use function strlen;
use function substr;

/**
 * Resolves absolute and project-relative paths for TreeGuard directory scanning.
 *
 * The project root is addressed as "." both in the configured paths and in the
 * relative paths reported for it, so every directory below it keeps the same
 * root-relative form whether the scan started at the root or deeper.
 */
final class TreeGuardPathResolver
{
    /**
     * Returns an absolute path for a configured source path.
     */
    public function absolute(string $root, string $path): string
    {
        $path = rtrim($path, '/');

        if ($path === '' || $path === '.') {
            return rtrim($root, '/');
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($root . '/' . $path, '/');
    }

    /**
     * Returns a path relative to the TreeGuard project root when possible.
     */
    public function relative(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $path = rtrim(str_replace('\\', '/', $path), '/');

        if ($path === $root) {
            return '.';
        }

        return str_starts_with($path, $root . '/') ? substr($path, strlen($root) + 1) : $path;
    }

    /**
     * Returns the relative path of one entry inside a scanned directory.
     */
    public function child(string $relativeDir, string $name): string
    {
        return $relativeDir === '.' ? $name : $relativeDir . '/' . $name;
    }

    /**
     * Returns the relative path prefix every descendant of a directory starts with.
     */
    public function descendantPrefix(string $relativeDir): string
    {
        return $relativeDir === '.' ? '' : $relativeDir . '/';
    }
}
