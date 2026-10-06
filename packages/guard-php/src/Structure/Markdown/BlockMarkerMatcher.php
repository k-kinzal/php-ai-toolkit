<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown;

use function preg_match;
use function str_starts_with;

/**
 * Recognizes the block markers that end a paragraph without being headings.
 */
final class BlockMarkerMatcher
{
    /**
     * Returns whether a line whose indentation is removed is a thematic break such as "***" or "- - -".
     */
    public function isThematicBreak(string $content): bool
    {
        return preg_match('/^(?:(?:\*[ \t]*){3,}|(?:-[ \t]*){3,}|(?:_[ \t]*){3,})$/', $content) === 1;
    }

    /**
     * Returns whether a line whose indentation is removed starts a bullet or ordered list item.
     */
    public function isListItem(string $content): bool
    {
        return preg_match('/^(?:[-+*]|\d{1,9}[.)])(?:[ \t]|$)/', $content) === 1;
    }

    /**
     * Returns whether a line whose indentation is removed belongs to a block quote.
     */
    public function isBlockQuote(string $content): bool
    {
        return str_starts_with($content, '>');
    }
}
