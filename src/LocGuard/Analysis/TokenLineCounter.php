<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;

use PhpToken;

use function str_ends_with;
use function substr_count;

/**
 * Counts physical lines and non-comment lines of PHP source.
 */
final class TokenLineCounter
{
    /**
     * Creates a line counter from token line resolution.
     */
    public function __construct(
        private readonly CodeTokenLineResolver $codeLineResolver = new CodeTokenLineResolver(),
    ) {
    }

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
            foreach ($this->codeLineResolver->lineNumbers($token) as $lineNumber) {
                $lines[$lineNumber] = true;
            }
        }

        return count($lines);
    }
}
