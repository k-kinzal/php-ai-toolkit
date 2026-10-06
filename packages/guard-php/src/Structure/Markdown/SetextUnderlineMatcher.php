<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown;

use function preg_match;

/**
 * Recognizes setext heading underlines made of "=" or "-" characters.
 */
final class SetextUnderlineMatcher
{
    /**
     * Returns the heading level of an underline whose indentation is already removed, or null.
     *
     * An "=" underline makes a level 1 heading and a "-" underline a level 2 heading.
     */
    public function level(string $content): ?int
    {
        if (preg_match('/^=+[ \t]*$/', $content) === 1) {
            return 1;
        }

        if (preg_match('/^-+[ \t]*$/', $content) === 1) {
            return 2;
        }

        return null;
    }
}
