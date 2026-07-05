<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Splits a normalized path around a configured directory marker.
 */
final class PathMarkerSplitter
{
    /**
     * @return array{string, string}|null
     */
    public function split(string $path, string $marker): ?array
    {
        $pos = strpos($path, $marker);
        if ($pos === false) {
            return null;
        }

        $root = substr($path, 0, $pos);
        $relativePath = substr($path, $pos + strlen($marker));

        return [$root, $relativePath];
    }
}
