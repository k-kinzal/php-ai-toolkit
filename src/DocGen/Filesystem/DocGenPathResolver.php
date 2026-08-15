<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Filesystem;

use function rtrim;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Resolves paths between absolute and project-relative form.
 */
final class DocGenPathResolver
{
    /**
     * Resolves a possibly relative path against a base directory.
     */
    public function resolve(string $base, string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '/')) {
            return $normalized;
        }

        return rtrim(str_replace('\\', '/', $base), '/') . '/' . $normalized;
    }

    /**
     * Returns a path relative to a base directory when it is inside it.
     */
    public function relative(string $base, string $path): string
    {
        $normalizedBase = rtrim(str_replace('\\', '/', $base), '/') . '/';
        $normalizedPath = str_replace('\\', '/', $path);
        if (str_starts_with($normalizedPath, $normalizedBase)) {
            return substr($normalizedPath, strlen($normalizedBase));
        }

        return $normalizedPath;
    }
}
