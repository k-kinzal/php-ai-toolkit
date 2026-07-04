<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_pop;
use function count;
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
use const T_MATCH;
use const T_WHILE;

/**
 * Calculates cyclomatic complexity for one function-like metric.
 */
final class CyclomaticComplexityCalculator
{
    /**
     * Calculates complexity while excluding nested function-like bodies.
     *
     * @param list<PhpToken> $tokens
     * @param list<FunctionMetric> $metrics
     */
    public function calculate(array $tokens, FunctionMetric $metric, array $metrics): int
    {
        $complexity = 1;
        $state = $this->newState();
        for ($index = $metric->bodyStartIndex + 1; $index < $metric->bodyEndIndex; $index++) {
            if ($this->isInsideNestedFunction($index, $metric, $metrics)) {
                continue;
            }

            $complexity += $this->decisionWeight($tokens[$index], $state);
            $this->advanceState($tokens[$index], $state);
        }

        return $complexity;
    }

    /**
     * @return array{paren: int, bracket: int, brace: int, pending_match_paren: ?int, matches: list<array{paren: int, bracket: int, brace: int}>}
     */
    private function newState(): array
    {
        return ['paren' => 0, 'bracket' => 0, 'brace' => 0, 'pending_match_paren' => null, 'matches' => []];
    }

    /**
     * @param array{paren: int, bracket: int, brace: int, pending_match_paren: ?int, matches: list<array{paren: int, bracket: int, brace: int}>} $state
     */
    private function decisionWeight(PhpToken $token, array $state): int
    {
        if ($token->id === T_DOUBLE_ARROW && $this->isMatchArm($state)) {
            return 1;
        }

        return in_array($token->id, $this->decisionTokenIds(), true) || $token->text === '?' ? 1 : 0;
    }

    /**
     * @param array{paren: int, bracket: int, brace: int, pending_match_paren: ?int, matches: list<array{paren: int, bracket: int, brace: int}>} $state
     */
    private function isMatchArm(array $state): bool
    {
        foreach ($state['matches'] as $match) {
            if ($state['paren'] === $match['paren']
                && $state['bracket'] === $match['bracket']
                && $state['brace'] === $match['brace']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{paren: int, bracket: int, brace: int, pending_match_paren: ?int, matches: list<array{paren: int, bracket: int, brace: int}>} $state
     */
    private function advanceState(PhpToken $token, array &$state): void
    {
        if ($token->id === T_MATCH) {
            $state['pending_match_paren'] = $state['paren'];
        }

        if ($token->text === '(') {
            $state['paren']++;
        } elseif ($token->text === ')' && $state['paren'] > 0) {
            $state['paren']--;
        } elseif ($token->text === '[') {
            $state['bracket']++;
        } elseif ($token->text === ']' && $state['bracket'] > 0) {
            $state['bracket']--;
        } elseif ($token->text === '{') {
            $state['brace']++;
            if ($state['pending_match_paren'] === $state['paren']) {
                $state['matches'][] = [
                    'paren' => $state['paren'],
                    'bracket' => $state['bracket'],
                    'brace' => $state['brace'],
                ];
                $state['pending_match_paren'] = null;
            }
        } elseif ($token->text === '}') {
            if ($state['matches'] !== [] && $state['matches'][count($state['matches']) - 1]['brace'] === $state['brace']) {
                array_pop($state['matches']);
            }
            if ($state['brace'] > 0) {
                $state['brace']--;
            }
        }
    }

    /**
     * @return list<int>
     */
    private function decisionTokenIds(): array
    {
        return [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_DO, T_CASE, T_CATCH, T_BOOLEAN_AND, T_BOOLEAN_OR, T_COALESCE];
    }

    /**
     * @param list<FunctionMetric> $metrics
     */
    private function isInsideNestedFunction(int $index, FunctionMetric $metric, array $metrics): bool
    {
        foreach ($metrics as $candidate) {
            if ($candidate === $metric) {
                continue;
            }

            if ($candidate->bodyStartIndex > $metric->bodyStartIndex
                && $candidate->bodyEndIndex < $metric->bodyEndIndex
                && $index >= $candidate->bodyStartIndex
                && $index <= $candidate->bodyEndIndex
            ) {
                return true;
            }
        }

        return false;
    }
}
