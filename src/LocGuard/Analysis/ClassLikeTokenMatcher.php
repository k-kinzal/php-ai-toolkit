<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function defined;
use function in_array;
use PhpToken;

use function strtolower;

use const T_CLASS;
use const T_INTERFACE;
use const T_STRING;
use const T_TRAIT;

/**
 * Identifies class-like declaration tokens across PHP tokenizer versions.
 */
final class ClassLikeTokenMatcher
{
    /** @readonly */
    private PhpTokenNavigator $tokenNavigator;

    /**
     * Creates a matcher backed by token navigation.
     */
    public function __construct(?PhpTokenNavigator $tokenNavigator = null)
    {
        $this->tokenNavigator = $tokenNavigator ?? new PhpTokenNavigator();
    }

    /**
     * Reports whether the token at the index can start a class-like declaration.
     *
     * @param list<PhpToken> $tokens
     */
    public function isClassLikeToken(array $tokens, int $index): bool
    {
        $token = $tokens[$index];
        if (in_array($token->id, [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
            return true;
        }

        if (!$this->isEnumToken($token)) {
            return false;
        }

        if ($token->id !== T_STRING) {
            return true;
        }

        $next = $this->tokenNavigator->nextSignificant($tokens, $index);

        return $next !== null && $next->id === T_STRING;
    }

    /**
     * Reports whether the token represents an enum declaration keyword.
     */
    public function isEnumToken(PhpToken $token): bool
    {
        if (defined('T_ENUM') && $token->id === constant('T_ENUM')) {
            return true;
        }

        return $token->id === T_STRING && strtolower($token->text) === 'enum';
    }
}
