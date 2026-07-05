<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpToken;

/**
 * Reads metrics for arrow-function expressions.
 */
final class ArrowFunctionMetricReader
{
    /**
     * Creates an arrow-function metric reader from body location and token navigation.
     */
    public function __construct(
        private readonly FunctionBodyLocator $bodyLocator = new FunctionBodyLocator(),
        private readonly PhpTokenNavigator $tokenNavigator = new PhpTokenNavigator(),
    ) {
    }

    /**
     * Returns the metric for an arrow-function token.
     *
     * @param list<PhpToken> $tokens
     */
    public function metric(array $tokens, int $index): ?FunctionMetric
    {
        $bodyStart = $this->bodyLocator->arrowBodyStart($tokens, $index);
        if ($bodyStart === null) {
            return null;
        }

        $bodyEnd = $this->bodyLocator->arrowBodyEnd($tokens, $bodyStart);
        $endToken = $this->tokenNavigator->previousSignificantIndex($tokens, $bodyEnd);
        if ($endToken === null || $endToken <= $bodyStart) {
            return null;
        }

        return new FunctionMetric('function', '{closure}', $tokens[$index]->line, $tokens[$endToken]->line, $bodyStart, $bodyEnd);
    }
}
