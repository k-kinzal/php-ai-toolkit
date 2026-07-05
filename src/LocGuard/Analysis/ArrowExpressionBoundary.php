<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;
use function in_array;

use PhpToken;

/**
 * Finds the exclusive end of an arrow-function expression.
 */
final class ArrowExpressionBoundary
{
    /**
     * Returns the exclusive end index for an arrow function expression.
     *
     * @param list<PhpToken> $tokens
     */
    public function end(array $tokens, int $bodyStart): int
    {
        $parenDepth = 0;
        $bracketDepth = 0;
        $braceDepth = 0;

        for ($i = $bodyStart + 1; $i < count($tokens); $i++) {
            if ($this->isTerminator($tokens[$i], $parenDepth, $bracketDepth, $braceDepth)) {
                return $i;
            }

            if ($tokens[$i]->text === '(') {
                $parenDepth++;
            } elseif ($tokens[$i]->text === ')' && $parenDepth > 0) {
                $parenDepth--;
            } elseif ($tokens[$i]->text === '[') {
                $bracketDepth++;
            } elseif ($tokens[$i]->text === ']' && $bracketDepth > 0) {
                $bracketDepth--;
            } elseif ($tokens[$i]->text === '{') {
                $braceDepth++;
            } elseif ($tokens[$i]->text === '}' && $braceDepth > 0) {
                $braceDepth--;
            }
        }

        return count($tokens);
    }

    /**
     * Reports whether the token terminates an arrow expression at the given nesting depth.
     */
    public function isTerminator(PhpToken $token, int $parenDepth, int $bracketDepth, int $braceDepth): bool
    {
        if ($parenDepth !== 0 || $bracketDepth !== 0 || $braceDepth !== 0) {
            return false;
        }

        return in_array($token->text, [';', ',', ')', ']', '}'], true);
    }
}
