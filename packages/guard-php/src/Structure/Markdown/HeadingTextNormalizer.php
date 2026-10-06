<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown;

use function preg_replace;
use function trim;

/**
 * Normalizes heading text so that whitespace-only edits are not structural changes.
 */
final class HeadingTextNormalizer
{
    /**
     * Collapses runs of spaces and tabs into one space and trims both ends.
     */
    public function normalize(string $text): string
    {
        return trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);
    }
}
