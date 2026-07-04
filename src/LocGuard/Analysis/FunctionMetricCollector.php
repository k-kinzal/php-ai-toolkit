<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_key_exists;
use function count;
use function in_array;

use PhpToken;

use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_DOUBLE_ARROW;
use const T_DOUBLE_COLON;
use const T_ENUM;
use const T_FN;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_STRING;
use const T_TRAIT;
use const T_WHITESPACE;

/**
 * Collects function, closure, method, and cyclomatic-complexity metrics.
 */
final class FunctionMetricCollector
{
    /**
     * Creates a function metric collector from a complexity calculator.
     */
    public function __construct(
        private readonly CyclomaticComplexityCalculator $complexityCalculator = new CyclomaticComplexityCalculator(),
    ) {
    }

    /**
     * Collects function-like metrics from tokenized PHP source.
     *
     * @param list<PhpToken> $tokens
     * @return list<FunctionMetric>
     */
    public function collect(array $tokens): array
    {
        $metrics = $this->collectFunctionLines($tokens);
        $this->fillCyclomaticComplexity($tokens, $metrics);

        return $metrics;
    }

    /**
     * @param list<PhpToken> $tokens
     * @return list<FunctionMetric>
     */
    private function collectFunctionLines(array $tokens): array
    {
        $metrics = [];
        $classNames = [];
        $braceStack = [];
        $classBodyStarts = [];
        $functionBodyStarts = [];

        foreach ($tokens as $index => $token) {
            if ($this->isClassLikeDeclaration($tokens, $index)) {
                $bodyStart = $this->findNextTokenText($tokens, $index, '{');
                if ($bodyStart !== null) {
                    $classBodyStarts[$bodyStart] = $this->readClassName($tokens, $index);
                }
            }

            if ($token->id === T_FUNCTION || $token->id === T_FN) {
                $metric = $this->readFunctionMetric($tokens, $index, $braceStack, $classNames);
                if ($metric !== null) {
                    $metrics[] = $metric;
                    $functionBodyStarts[$metric->bodyStartIndex] = true;
                }
            }

            $this->updateBraceStack($token, $index, $braceStack, $classNames, $classBodyStarts, $functionBodyStarts);
        }

        return $metrics;
    }

    /**
     * @param list<PhpToken> $tokens
     * @param list<string> $braceStack
     * @param list<string> $classNames
     */
    private function readFunctionMetric(
        array $tokens,
        int $index,
        array $braceStack,
        array $classNames,
    ): ?FunctionMetric {
        if ($tokens[$index]->id === T_FN) {
            return $this->readArrowFunctionMetric($tokens, $index);
        }

        $bodyStart = $this->findFunctionBodyStart($tokens, $index);
        if ($bodyStart === null) {
            return null;
        }

        $bodyEnd = $this->findMatchingBrace($tokens, $bodyStart);
        if ($bodyEnd === null) {
            return null;
        }

        $isMethod = ($braceStack[count($braceStack) - 1] ?? null) === 'class';
        $name = $this->readFunctionName($tokens, $index);
        if ($isMethod && $classNames !== []) {
            $name = $classNames[count($classNames) - 1] . '::' . $name;
        }

        return new FunctionMetric($isMethod ? 'method' : 'function', $name, $tokens[$index]->line, $tokens[$bodyEnd]->line, $bodyStart, $bodyEnd);
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function readArrowFunctionMetric(array $tokens, int $index): ?FunctionMetric
    {
        $bodyStart = $this->findNextTokenId($tokens, $index, T_DOUBLE_ARROW);
        if ($bodyStart === null) {
            return null;
        }

        $bodyEnd = $this->findArrowExpressionEnd($tokens, $bodyStart);
        $endToken = $this->previousSignificantTokenIndex($tokens, $bodyEnd);
        if ($endToken === null || $endToken <= $bodyStart) {
            return null;
        }

        return new FunctionMetric('function', '{closure}', $tokens[$index]->line, $tokens[$endToken]->line, $bodyStart, $bodyEnd);
    }

    /**
     * @param list<PhpToken> $tokens
     * @param list<FunctionMetric> $metrics
     */
    private function fillCyclomaticComplexity(array $tokens, array $metrics): void
    {
        foreach ($metrics as $metric) {
            $metric->cyclomaticComplexity = $this->complexityCalculator->calculate($tokens, $metric, $metrics);
        }
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

    /**
     * @param list<PhpToken> $tokens
     */
    private function readClassName(array $tokens, int $index): string
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
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
    private function readFunctionName(array $tokens, int $index): string
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

    /**
     * @param list<PhpToken> $tokens
     */
    private function findFunctionBodyStart(array $tokens, int $index): ?int
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->text === '{') {
                return $i;
            }
            if ($tokens[$i]->text === ';') {
                return null;
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
    private function findNextTokenId(array $tokens, int $index, int $id): ?int
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->id === $id) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function findArrowExpressionEnd(array $tokens, int $bodyStart): int
    {
        $parenDepth = 0;
        $bracketDepth = 0;
        $braceDepth = 0;

        for ($i = $bodyStart + 1; $i < count($tokens); $i++) {
            if ($this->isArrowExpressionTerminator($tokens[$i], $parenDepth, $bracketDepth, $braceDepth)) {
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

    private function isArrowExpressionTerminator(PhpToken $token, int $parenDepth, int $bracketDepth, int $braceDepth): bool
    {
        if ($parenDepth !== 0 || $bracketDepth !== 0 || $braceDepth !== 0) {
            return false;
        }

        return in_array($token->text, [';', ',', ')', ']', '}'], true);
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function previousSignificantTokenIndex(array $tokens, int $exclusiveEnd): ?int
    {
        for ($i = $exclusiveEnd - 1; $i >= 0; $i--) {
            if (!in_array($tokens[$i]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<string> $braceStack
     * @param list<string> $classNames
     * @param array<int, string> $classBodyStarts
     * @param array<int, bool> $functionBodyStarts
     */
    private function updateBraceStack(PhpToken $token, int $index, array &$braceStack, array &$classNames, array $classBodyStarts, array $functionBodyStarts): void
    {
        if ($token->text === '{') {
            $this->pushBrace($index, $braceStack, $classNames, $classBodyStarts, $functionBodyStarts);
        } elseif ($token->text === '}') {
            $context = array_pop($braceStack);
            if ($context === 'class') {
                array_pop($classNames);
            }
        }
    }

    /**
     * @param list<string> $braceStack
     * @param list<string> $classNames
     * @param array<int, string> $classBodyStarts
     * @param array<int, bool> $functionBodyStarts
     */
    private function pushBrace(int $index, array &$braceStack, array &$classNames, array $classBodyStarts, array $functionBodyStarts): void
    {
        if (array_key_exists($index, $classBodyStarts)) {
            $braceStack[] = 'class';
            $classNames[] = $classBodyStarts[$index];
        } elseif (array_key_exists($index, $functionBodyStarts)) {
            $braceStack[] = 'function';
        } else {
            $braceStack[] = 'block';
        }
    }
}
