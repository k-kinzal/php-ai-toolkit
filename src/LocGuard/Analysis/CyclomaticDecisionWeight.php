<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function in_array;

use PhpToken;

use const T_BOOLEAN_AND;
use const T_BOOLEAN_OR;
use const T_CASE;
use const T_CATCH;
use const T_COALESCE;
use const T_DO;
use const T_DOUBLE_ARROW;
use const T_ELSEIF;
use const T_FOR;
use const T_FOREACH;
use const T_IF;
use const T_WHILE;

/**
 * Calculates the cyclomatic-complexity contribution of one token.
 */
final class CyclomaticDecisionWeight
{
    /**
     * Returns the complexity weight contributed by the token.
     */
    public function weight(PhpToken $token, CyclomaticComplexityState $state): int
    {
        if ($token->id === T_DOUBLE_ARROW && $state->isAtMatchArm()) {
            return 1;
        }

        return in_array($token->id, $this->decisionTokenIds(), true) || $token->text === '?' ? 1 : 0;
    }

    /**
     * Returns token ids that always add one decision point.
     *
     * @return list<int>
     */
    public function decisionTokenIds(): array
    {
        return [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_DO, T_CASE, T_CATCH, T_BOOLEAN_AND, T_BOOLEAN_OR, T_COALESCE];
    }
}
