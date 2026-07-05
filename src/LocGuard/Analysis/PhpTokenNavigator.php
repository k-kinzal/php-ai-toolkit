<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;
use function in_array;

use PhpToken;

use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_WHITESPACE;

/**
 * Locates structural tokens inside tokenized PHP source.
 */
final class PhpTokenNavigator
{
    /**
     * Returns the nearest previous token that is not whitespace or a comment.
     *
     * @param list<PhpToken> $tokens
     */
    public function previousSignificant(array $tokens, int $index): ?PhpToken
    {
        $previousIndex = $this->previousSignificantIndex($tokens, $index);

        return $previousIndex === null ? null : $tokens[$previousIndex];
    }

    /**
     * Returns the nearest next token that is not whitespace or a comment.
     *
     * @param list<PhpToken> $tokens
     */
    public function nextSignificant(array $tokens, int $index): ?PhpToken
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (!in_array($tokens[$i]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $tokens[$i];
            }
        }

        return null;
    }

    /**
     * Returns the nearest previous significant token index before the exclusive end.
     *
     * @param list<PhpToken> $tokens
     */
    public function previousSignificantIndex(array $tokens, int $exclusiveEnd): ?int
    {
        for ($i = $exclusiveEnd - 1; $i >= 0; $i--) {
            if (!in_array($tokens[$i]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns the index of the next token with the requested text.
     *
     * @param list<PhpToken> $tokens
     */
    public function nextText(array $tokens, int $index, string $text): ?int
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->text === $text) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns the index of the next token with the requested id.
     *
     * @param list<PhpToken> $tokens
     */
    public function nextId(array $tokens, int $index, int $id): ?int
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->id === $id) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns the closing brace index that matches the opening brace.
     *
     * @param list<PhpToken> $tokens
     */
    public function matchingBrace(array $tokens, int $openIndex): ?int
    {
        $depth = 0;
        for ($i = $openIndex; $i < count($tokens); $i++) {
            if ($tokens[$i]->text === '{') {
                $depth++;
            } elseif ($tokens[$i]->text === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }
}
