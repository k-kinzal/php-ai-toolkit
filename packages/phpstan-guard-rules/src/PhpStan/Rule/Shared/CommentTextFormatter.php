<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

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
        $trimmed = trim($text, " \n\r\t\v\0");
        if (mb_strlen($trimmed) > 80) {
            return mb_substr($trimmed, 0, 80) . '...';
        }

        return $trimmed;
    }
}
