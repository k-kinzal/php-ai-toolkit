<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;
use function explode;
use function in_array;

use PhpToken;

use function str_ends_with;
use function substr_count;

use const T_CLOSE_TAG;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_OPEN_TAG;
use const T_WHITESPACE;

use function trim;

/**
 * Counts physical lines and non-comment lines of PHP source.
 */
final class TokenLineCounter
{
    /**
     * Counts physical lines in the source exactly as stored on disk.
     */
    public function physicalLines(string $source): int
    {
        if ($source === '') {
            return 0;
        }

        return substr_count($source, "\n") + (str_ends_with($source, "\n") ? 0 : 1);
    }

    /**
     * Counts lines containing PHP code, excluding blank lines, comments, and PHP tags.
     *
     * @param list<PhpToken> $tokens
     */
    public function nonCommentLines(array $tokens): int
    {
        $lines = [];
        foreach ($tokens as $token) {
            if ($this->isNonCodeToken($token)) {
                continue;
            }

            $this->markCodeLines($lines, $token);
        }

        return count($lines);
    }

    private function isNonCodeToken(PhpToken $token): bool
    {
        return in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG], true);
    }

    /**
     * @param array<int, bool> $lines
     */
    private function markCodeLines(array &$lines, PhpToken $token): void
    {
        $parts = explode("\n", $token->text);
        foreach ($parts as $offset => $part) {
            if (trim($part) !== '') {
                $lines[$token->line + $offset] = true;
            }
        }
    }
}
