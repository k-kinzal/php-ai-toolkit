<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpToken;

/**
 * Assigns cyclomatic complexity values to collected function metrics.
 */
final class FunctionMetricComplexityAssigner
{
    /**
     * Creates an assigner backed by the cyclomatic-complexity calculator.
     */
    public function __construct(
        private readonly CyclomaticComplexityCalculator $complexityCalculator = new CyclomaticComplexityCalculator(),
    ) {
    }

    /**
     * Fills each metric with its calculated cyclomatic complexity.
     *
     * @param list<PhpToken> $tokens
     * @param list<FunctionMetric> $metrics
     */
    public function assign(array $tokens, array $metrics): void
    {
        foreach ($metrics as $metric) {
            $metric->cyclomaticComplexity = $this->complexityCalculator->calculate($tokens, $metric, $metrics);
        }
    }
}
