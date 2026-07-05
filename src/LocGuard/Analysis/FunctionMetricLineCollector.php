<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpToken;

use const T_FN;
use const T_FUNCTION;

/**
 * Collects function-like line metrics from tokenized PHP source.
 */
final class FunctionMetricLineCollector
{
    /**
     * Creates a line collector from declaration, navigation, and metric readers.
     */
    public function __construct(
        private readonly ClassLikeDeclarationReader $classLikeDeclarationReader = new ClassLikeDeclarationReader(),
        private readonly PhpTokenNavigator $tokenNavigator = new PhpTokenNavigator(),
        private readonly BlockFunctionMetricReader $blockFunctionMetricReader = new BlockFunctionMetricReader(),
        private readonly ArrowFunctionMetricReader $arrowFunctionMetricReader = new ArrowFunctionMetricReader(),
    ) {
    }

    /**
     * Collects function, closure, method, and arrow-function line metrics.
     *
     * @param list<PhpToken> $tokens
     * @return list<FunctionMetric>
     */
    public function collect(array $tokens): array
    {
        $metrics = [];
        $scanState = new FunctionScanState();

        foreach ($tokens as $index => $token) {
            if ($this->classLikeDeclarationReader->isDeclaration($tokens, $index)) {
                $bodyStart = $this->tokenNavigator->nextText($tokens, $index, '{');
                if ($bodyStart !== null) {
                    $scanState->registerClassBody($bodyStart, $this->classLikeDeclarationReader->name($tokens, $index));
                }
            }

            if ($token->id === T_FUNCTION) {
                $metric = $this->blockFunctionMetricReader->metric($tokens, $index, $scanState);
                if ($metric !== null) {
                    $metrics[] = $metric;
                    $scanState->registerFunctionBody($metric->bodyStartIndex);
                }
            } elseif ($token->id === T_FN) {
                $metric = $this->arrowFunctionMetricReader->metric($tokens, $index);
                if ($metric !== null) {
                    $metrics[] = $metric;
                    $scanState->registerFunctionBody($metric->bodyStartIndex);
                }
            }

            $scanState->advance($token, $index);
        }

        return $metrics;
    }
}
