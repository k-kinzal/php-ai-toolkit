<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Filesystem;

use function rtrim;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Resolves absolute and project-relative paths for ScopeGuard file scanning.
 *
 * @visibility parent
 */
final class ScopeGuardPathResolver
{
    /**
     * Returns an absolute path for a configured source path.
     */
    public function absolute(string $root, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim($root . '/' . $path, '/');
    }

    /**
     * Returns a path relative to the ScopeGuard project root when possible.
     */
    public function relative(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
