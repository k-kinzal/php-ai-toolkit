<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;
use function in_array;

use PhpToken;

use const T_CLASS;
use const T_DOUBLE_COLON;
use const T_EXTENDS;
use const T_IMPLEMENTS;
use const T_INTERFACE;
use const T_STRING;
use const T_TRAIT;

/**
 * Reads class-like declarations from tokenized PHP source.
 */
final class ClassLikeDeclarationReader
{
    /** @readonly */
    private PhpTokenNavigator $tokenNavigator;

    /**
     * Creates a reader backed by token navigation.
     */
    public function __construct(?PhpTokenNavigator $tokenNavigator = null)
    {
        $this->tokenNavigator = $tokenNavigator ?? new PhpTokenNavigator();
    }

    /**
     * Checks whether the token at the index begins a class-like declaration.
     *
     * @param list<PhpToken> $tokens
     */
    public function isDeclaration(array $tokens, int $index): bool
    {
        $token = $tokens[$index];
        $enumTokenId = defined('T_ENUM') ? constant('T_ENUM') : -1;
        if (!in_array($token->id, [T_CLASS, T_INTERFACE, T_TRAIT, $enumTokenId], true)) {
            return false;
        }

        $previous = $this->tokenNavigator->previousSignificant($tokens, $index);
        return $previous === null || $previous->id !== T_DOUBLE_COLON;
    }

    /**
     * Returns the class-like kind represented by the declaration token.
     */
    public function kind(PhpToken $token): string
    {
        if ($token->id === T_INTERFACE) {
            return 'interface';
        }

        if ($token->id === T_TRAIT) {
            return 'trait';
        }

        if (defined('T_ENUM') && $token->id === constant('T_ENUM')) {
            return 'enum';
        }

        return 'class';
    }

    /**
     * Returns the declared name, or an anonymous name derived from the line.
     *
     * @param list<PhpToken> $tokens
     */
    public function name(array $tokens, int $index): string
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (in_array($tokens[$i]->id, [T_EXTENDS, T_IMPLEMENTS], true) || $tokens[$i]->text === '(') {
                return 'anonymous@' . $tokens[$index]->line;
            }

            if ($tokens[$i]->id === T_STRING) {
                return $tokens[$i]->text;
            }

            if ($tokens[$i]->text === '{') {
                break;
            }
        }

        return 'anonymous@' . $tokens[$index]->line;
    }
}
