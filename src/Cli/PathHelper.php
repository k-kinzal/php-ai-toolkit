<?php

declare(strict_types=1);

namespace PhpStanAiRules\Cli;

use function array_slice;
use function count;
use function explode;
use function implode;
use function str_repeat;
use function str_replace;
use function rtrim;

/**
 * Computes relative paths between directories for symlink creation.
 */
final class PathHelper
{
    /**
     * Computes the relative path from one directory to another.
     *
     * Both paths must be absolute. The result is suitable for use
     * as a symlink target (relative to the symlink's parent directory).
     *
     * @param string $from absolute path of the source directory
     * @param string $to   absolute path of the target directory
     */
    public static function relativePath(string $from, string $to): string
    {
        $from = self::normalizePath($from);
        $to = self::normalizePath($to);

        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);

        $commonLength = 0;
        $maxCommon = min(count($fromParts), count($toParts));

        while ($commonLength < $maxCommon && $fromParts[$commonLength] === $toParts[$commonLength]) {
            $commonLength++;
        }

        $upCount = count($fromParts) - $commonLength;
        $remaining = array_slice($toParts, $commonLength);

        $relative = str_repeat('../', $upCount) . implode('/', $remaining);

        return rtrim($relative, '/');
    }

    private static function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return rtrim($path, '/');
    }
}
