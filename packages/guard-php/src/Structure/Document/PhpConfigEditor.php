<?php

declare(strict_types=1);

namespace Guard\Structure\Document;

use Guard\Diagnostic\PolicyException;
use PhpToken;

/**
 * Replaces configuration call arguments without executing PHP or rewriting surrounding code.
 */
final class PhpConfigEditor
{
    /**
     * Replaces one call argument or adds a setter to the returned configuration object.
     * @throws PolicyException when the target is ambiguous or there is no return expression
     */
    public function write(string $source, string $method, string $argument): string
    {
        $tokens = array_values(PhpToken::tokenize($source));
        $ranges = $this->arguments($tokens, $method);
        if (count($ranges) > 1) {
            throw new PolicyException('PHP configuration contains multiple ' . $method . '() calls. Keep one configuration call so its value can be checked and set unambiguously.');
        }
        if ($ranges !== []) {
            [$start, $end] = $ranges[0];
            return substr($source, 0, $start) . $argument . substr($source, $end);
        }
        [$start, $end] = $this->returnedExpression($tokens);
        return substr($source, 0, $start) . '(' . substr($source, $start, $end - $start) . ')->' . $method . '(' . $argument . ')' . substr($source, $end);
    }

    /**
     * Locates complete argument spans of method calls, ignoring comments and string contents.
     * @param list<PhpToken> $tokens
     * @return list<array{int, int}>
     */
    public function arguments(array $tokens, string $method): array
    {
        $ranges = [];
        $previous = null;
        foreach ($tokens as $index => $token) {
            if ($token->isIgnorable()) {
                continue;
            }
            if ($token->id === T_STRING && strcasecmp($token->text, $method) === 0 && $previous !== null && $previous->id === T_OBJECT_OPERATOR) {
                $open = $this->next($tokens, $index + 1);
                if (isset($tokens[$open]) && $tokens[$open]->text === '(') {
                    $end = $this->closing($tokens, $open + 1, ')');
                    $ranges[] = [$tokens[$open]->pos + 1, $end];
                }
            }
            $previous = $token;
        }
        return $ranges;
    }

    /**
     * Finds the single top-level return expression used to construct a configuration.
     * @param list<PhpToken> $tokens
     * @return array{int, int}
     * @throws PolicyException when no unique return expression is available
     */
    public function returnedExpression(array $tokens): array
    {
        $ranges = $this->returns($tokens);
        if (count($ranges) !== 1 || $ranges[0][0] === $ranges[0][1]) {
            throw new PolicyException('PHP configuration must have one top-level return expression to add a missing setting. Return the configuration object explicitly.');
        }
        return $ranges[0];
    }

    /**
     * Locates top-level returns while ignoring returns inside functions and closures.
     * @param list<PhpToken> $tokens
     * @return list<array{int, int}>
     */
    public function returns(array $tokens): array
    {
        $depth = 0;
        $ranges = [];
        foreach ($tokens as $index => $token) {
            if ($token->text === '{') {
                $depth++;
            } elseif ($token->text === '}') {
                $depth--;
            } elseif ($token->id === T_RETURN && $depth === 0) {
                $start = $this->next($tokens, $index + 1);
                if (isset($tokens[$start])) {
                    $ranges[] = [$tokens[$start]->pos, $this->closing($tokens, $start, ';')];
                }
            }
        }
        return $ranges;
    }

    /**
     * Finds a delimiter outside nested expressions, treating strings as indivisible tokens.
     * @param list<PhpToken> $tokens
     * @throws PolicyException when the expression is incomplete
     */
    public function closing(array $tokens, int $start, string $delimiter): int
    {
        $depth = 0;
        for ($index = $start; $index < count($tokens); $index++) {
            $token = $tokens[$index];
            if ($depth === 0 && $token->text === $delimiter) {
                return $token->pos;
            }
            if (in_array($token->text, ['(', '[', '{'], true)) {
                $depth++;
            } elseif (in_array($token->text, [')', ']', '}'], true)) {
                $depth--;
            }
        }
        throw new PolicyException('PHP configuration expression has no closing ' . $delimiter . '. Correct its syntax before checking or setting values.');
    }

    /**
     * Skips whitespace and comments between PHP tokens.
     * @param list<PhpToken> $tokens
     */
    public function next(array $tokens, int $index): int
    {
        while (isset($tokens[$index]) && $tokens[$index]->isIgnorable()) {
            $index++;
        }
        return $index;
    }
}
