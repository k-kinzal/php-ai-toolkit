<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Markdown;

use function ltrim;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_contains;
use function strlen;

/**
 * Recognizes the opening and closing lines of backtick and tilde fenced code blocks.
 */
final class FenceMatcher
{
    /**
     * Returns the fence opened by a line whose indentation is already removed, or null.
     *
     * A backtick fence whose info string contains a backtick is inline code, not a fence.
     */
    public function open(string $content): ?Fence
    {
        if (preg_match('/^(`{3,}|~{3,})(.*)$/', $content, $matches) !== 1) {
            return null;
        }

        $marker = $matches[1][0];
        if ($marker === '`' && str_contains($matches[2], '`')) {
            return null;
        }

        return new Fence($marker, strlen($matches[1]));
    }

    /**
     * Returns whether a line closes the open fence: the same character, at least as long, and nothing else.
     */
    public function closes(Fence $fence, string $line): bool
    {
        $pattern = sprintf('/^%s{%d,}[ \t]*$/', preg_quote($fence->marker, '/'), $fence->length);

        return preg_match($pattern, ltrim($line, " \t")) === 1;
    }
}
