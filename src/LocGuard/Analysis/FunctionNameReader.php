<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;

use PhpToken;

use const T_STRING;

/**
 * Reads function-like names from tokenized declarations.
 */
final class FunctionNameReader
{
    /**
     * Returns a named function/method name, or the closure marker.
     *
     * @param list<PhpToken> $tokens
     */
    public function name(array $tokens, int $index): string
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->id === T_STRING) {
                return $tokens[$i]->text;
            }

            if ($tokens[$i]->text === '(') {
                return '{closure}';
            }
        }

        return '{closure}';
    }
}
