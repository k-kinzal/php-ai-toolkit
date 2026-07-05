<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Normalizes configured namespace prefixes.
 */
final class NamespacePrefixNormalizer
{
    /**
     * Converts separators and trims namespace boundaries.
     */
    public function normalize(string $prefix): string
    {
        return trim(str_replace('/', '\\', $prefix), '\\');
    }
}
