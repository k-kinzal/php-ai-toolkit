<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;
use function in_array;

use PhpToken;

use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_DOUBLE_COLON;
use const T_ENUM;
use const T_EXTENDS;
use const T_IMPLEMENTS;
use const T_INTERFACE;
use const T_STRING;
use const T_TRAIT;
use const T_WHITESPACE;

/**
 * Collects class-like declaration metrics from tokenized PHP source.
 */
final class ClassLikeMetricCollector
{
    /**
     * Collects line-count metrics for class, trait, interface, and enum declarations.
     *
     * @param list<PhpToken> $tokens
     * @return list<ClassLikeMetric>
     */
    public function collect(array $tokens): array
    {
        $metrics = [];

        foreach ($tokens as $index => $token) {
            if (!$this->isClassLikeDeclaration($tokens, $index)) {
                continue;
            }

            $bodyStart = $this->findNextTokenText($tokens, $index, '{');
            $bodyEnd = $bodyStart === null ? null : $this->findMatchingBrace($tokens, $bodyStart);
            if ($bodyEnd === null) {
                continue;
            }

            $metrics[] = new ClassLikeMetric(
                $this->kind($token),
                $this->readName($tokens, $index),
                $token->line,
                $tokens[$bodyEnd]->line,
            );
        }

        return $metrics;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function isClassLikeDeclaration(array $tokens, int $index): bool
    {
        $token = $tokens[$index];
        if (!in_array($token->id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            return false;
        }

        $previous = $this->previousSignificantToken($tokens, $index);
        return $previous === null || $previous->id !== T_DOUBLE_COLON;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function previousSignificantToken(array $tokens, int $index): ?PhpToken
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (!in_array($tokens[$i]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $tokens[$i];
            }
        }

        return null;
    }

    private function kind(PhpToken $token): string
    {
        if ($token->id === T_INTERFACE) {
            return 'interface';
        }

        if ($token->id === T_TRAIT) {
            return 'trait';
        }

        if ($token->id === T_ENUM) {
            return 'enum';
        }

        return 'class';
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function readName(array $tokens, int $index): string
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

    /**
     * @param list<PhpToken> $tokens
     */
    private function findNextTokenText(array $tokens, int $index, string $text): ?int
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->text === $text) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function findMatchingBrace(array $tokens, int $openIndex): ?int
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
