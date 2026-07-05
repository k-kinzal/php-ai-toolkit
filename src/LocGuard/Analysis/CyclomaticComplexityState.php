<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_pop;
use function count;

use PhpToken;

use const T_MATCH;

/**
 * Tracks nesting state needed for cyclomatic-complexity decisions.
 */
final class CyclomaticComplexityState
{
    private int $parenDepth = 0;

    private int $bracketDepth = 0;

    private int $braceDepth = 0;

    private ?int $pendingMatchParenDepth = null;

    /** @var list<array{paren: int, bracket: int, brace: int}> */
    private array $matchArmDepths = [];

    /**
     * Reports whether the current position is inside a top-level match arm list.
     */
    public function isAtMatchArm(): bool
    {
        foreach ($this->matchArmDepths as $matchArmDepth) {
            if ($this->parenDepth === $matchArmDepth['paren']
                && $this->bracketDepth === $matchArmDepth['bracket']
                && $this->braceDepth === $matchArmDepth['brace']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Advances the nesting state using one token.
     */
    public function advance(PhpToken $token): void
    {
        if ($token->id === T_MATCH) {
            $this->pendingMatchParenDepth = $this->parenDepth;
        }

        if ($token->text === '(') {
            $this->parenDepth++;
        } elseif ($token->text === ')' && $this->parenDepth > 0) {
            $this->parenDepth--;
        } elseif ($token->text === '[') {
            $this->bracketDepth++;
        } elseif ($token->text === ']' && $this->bracketDepth > 0) {
            $this->bracketDepth--;
        } elseif ($token->text === '{') {
            $this->braceDepth++;
            if ($this->pendingMatchParenDepth === $this->parenDepth) {
                $this->matchArmDepths[] = [
                    'paren' => $this->parenDepth,
                    'bracket' => $this->bracketDepth,
                    'brace' => $this->braceDepth,
                ];
                $this->pendingMatchParenDepth = null;
            }
        } elseif ($token->text === '}') {
            if ($this->matchArmDepths !== [] && $this->matchArmDepths[count($this->matchArmDepths) - 1]['brace'] === $this->braceDepth) {
                array_pop($this->matchArmDepths);
            }

            if ($this->braceDepth > 0) {
                $this->braceDepth--;
            }
        }
    }
}
