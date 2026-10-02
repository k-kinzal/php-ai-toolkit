<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Filesystem;

use function preg_replace;
use function rtrim;
use function str_starts_with;
use function substr;

/**
 * Normalizes configured paths and resolves them against the config directory.
 */
final class DocGuardPathResolver
{
    /**
     * Returns the absolute path of a configured path; absolute paths are returned unchanged.
     */
    public function resolve(string $root, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($root, '/') . '/' . $path;
    }

    /**
     * Removes leading "./" segments, repeated slashes, and a trailing slash.
     */
    public function normalize(string $path): string
    {
        $normalized = preg_replace('#/+#', '/', $path) ?? $path;
        while (str_starts_with($normalized, './')) {
            $normalized = substr($normalized, 2);
        }

        $normalized = $normalized === '/' ? $normalized : rtrim($normalized, '/');

        return $normalized === '' ? '.' : $normalized;
    }
}
