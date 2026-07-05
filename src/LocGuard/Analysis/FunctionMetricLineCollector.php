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
    /** @readonly */
    private ClassLikeDeclarationReader $classLikeDeclarationReader;

    /** @readonly */
    private PhpTokenNavigator $tokenNavigator;

    /** @readonly */
    private BlockFunctionMetricReader $blockFunctionMetricReader;

    /** @readonly */
    private ArrowFunctionMetricReader $arrowFunctionMetricReader;

    /**
     * Creates a line collector from declaration, navigation, and metric readers.
     */
    public function __construct(
        ?ClassLikeDeclarationReader $classLikeDeclarationReader = null,
        ?PhpTokenNavigator $tokenNavigator = null,
        ?BlockFunctionMetricReader $blockFunctionMetricReader = null,
        ?ArrowFunctionMetricReader $arrowFunctionMetricReader = null,
    ) {
        $this->classLikeDeclarationReader = $classLikeDeclarationReader ?? new ClassLikeDeclarationReader();
        $this->tokenNavigator = $tokenNavigator ?? new PhpTokenNavigator();
        $this->blockFunctionMetricReader = $blockFunctionMetricReader ?? new BlockFunctionMetricReader();
        $this->arrowFunctionMetricReader = $arrowFunctionMetricReader ?? new ArrowFunctionMetricReader();
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
