<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer;

use function rtrim;
use function str_replace;

/**
 * Normalizes filesystem paths for relative-path calculation.
 */
final class PathNormalizer
{
    /**
     * Converts separators to slashes and removes trailing slashes.
     */
    public function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return rtrim($path, '/');
    }
}
