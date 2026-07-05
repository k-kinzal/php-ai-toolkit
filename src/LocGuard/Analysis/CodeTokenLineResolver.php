<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function explode;
use function in_array;

use PhpToken;

use const T_CLOSE_TAG;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_OPEN_TAG;
use const T_WHITESPACE;

use function trim;

/**
 * Resolves executable source lines represented by one PHP token.
 */
final class CodeTokenLineResolver
{
    /**
     * Returns non-blank code line numbers represented by the token.
     *
     * @return list<int>
     */
    public function lineNumbers(PhpToken $token): array
    {
        if (in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG], true)) {
            return [];
        }

        $lines = [];
        $parts = explode("\n", $token->text);
        foreach ($parts as $offset => $part) {
            if (trim($part) !== '') {
                $lines[] = $token->line + $offset;
            }
        }

        return $lines;
    }
}
