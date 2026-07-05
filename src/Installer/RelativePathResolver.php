<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer;

use function array_slice;
use function count;
use function explode;
use function implode;
use function rtrim;
use function str_repeat;

/**
 * Computes relative paths between directories for symlink creation.
 */
final class RelativePathResolver
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
        $normalizer = new PathNormalizer();
        $from = $normalizer->normalize($from);
        $to = $normalizer->normalize($to);

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
}
