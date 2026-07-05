<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpToken;

/**
 * Reads metrics for block-bodied functions and methods.
 */
final class BlockFunctionMetricReader
{
    /**
     * Creates a block-function metric reader from body and name readers.
     */
    public function __construct(
        private readonly FunctionBodyLocator $bodyLocator = new FunctionBodyLocator(),
        private readonly FunctionNameReader $nameReader = new FunctionNameReader(),
    ) {
    }

    /**
     * Returns the metric for a block-bodied function-like token.
     *
     * @param list<PhpToken> $tokens
     */
    public function metric(array $tokens, int $index, FunctionScanState $scanState): ?FunctionMetric
    {
        $bodyStart = $this->bodyLocator->blockBodyStart($tokens, $index);
        if ($bodyStart === null) {
            return null;
        }

        $bodyEnd = $this->bodyLocator->blockBodyEnd($tokens, $bodyStart);
        if ($bodyEnd === null) {
            return null;
        }

        $isMethod = $scanState->isInClass();
        $name = $this->nameReader->name($tokens, $index);
        $className = $scanState->currentClassName();
        if ($isMethod && $className !== null) {
            $name = $className . '::' . $name;
        }

        return new FunctionMetric($isMethod ? 'method' : 'function', $name, $tokens[$index]->line, $tokens[$bodyEnd]->line, $bodyStart, $bodyEnd);
    }
}
