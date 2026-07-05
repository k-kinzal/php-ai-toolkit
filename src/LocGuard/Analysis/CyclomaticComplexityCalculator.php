<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpToken;

/**
 * Calculates cyclomatic complexity for one function-like metric.
 */
final class CyclomaticComplexityCalculator
{
    /** @readonly */
    private CyclomaticDecisionWeight $decisionWeight;

    /** @readonly */
    private NestedFunctionMetricRange $nestedFunctionMetricRange;

    /**
     * Creates a calculator from decision weighting and nested-range detection.
     */
    public function __construct(
        ?CyclomaticDecisionWeight $decisionWeight = null,
        ?NestedFunctionMetricRange $nestedFunctionMetricRange = null,
    ) {
        $this->decisionWeight = $decisionWeight ?? new CyclomaticDecisionWeight();
        $this->nestedFunctionMetricRange = $nestedFunctionMetricRange ?? new NestedFunctionMetricRange();
    }

    /**
     * Calculates complexity while excluding nested function-like bodies.
     *
     * @param list<PhpToken> $tokens
     * @param list<FunctionMetric> $metrics
     */
    public function calculate(array $tokens, FunctionMetric $metric, array $metrics): int
    {
        $complexity = 1;
        $state = new CyclomaticComplexityState();
        for ($index = $metric->bodyStartIndex + 1; $index < $metric->bodyEndIndex; $index++) {
            if ($this->nestedFunctionMetricRange->contains($index, $metric, $metrics)) {
                continue;
            }

            $complexity += $this->decisionWeight->weight($tokens[$index], $state);
            $state->advance($tokens[$index]);
        }

        return $complexity;
    }
}
