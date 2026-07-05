<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Formats comment text for rule error messages.
 */
final class CommentTextFormatter
{
    /**
     * Returns a trimmed comment truncated to a compact display length.
     */
    public function truncate(string $text): string
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) > 80) {
            return mb_substr($trimmed, 0, 80) . '...';
        }

        return $trimmed;
    }
}
