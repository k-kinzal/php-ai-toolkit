<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpToken;

/**
 * Collects function, closure, method, and cyclomatic-complexity metrics.
 */
final class FunctionMetricCollector
{
    /** @readonly */
    private FunctionMetricLineCollector $lineCollector;

    /** @readonly */
    private FunctionMetricComplexityAssigner $complexityAssigner;

    /**
     * Creates a function metric collector from a complexity calculator.
     */
    public function __construct(
        ?CyclomaticComplexityCalculator $complexityCalculator = null,
        ?FunctionMetricLineCollector $lineCollector = null,
        ?FunctionMetricComplexityAssigner $complexityAssigner = null,
    ) {
        $this->lineCollector = $lineCollector ?? new FunctionMetricLineCollector();
        $this->complexityAssigner = $complexityAssigner ?? new FunctionMetricComplexityAssigner($complexityCalculator ?? new CyclomaticComplexityCalculator());
    }

    /**
     * Collects function-like metrics from tokenized PHP source.
     *
     * @param list<PhpToken> $tokens
     * @return list<FunctionMetric>
     */
    public function collect(array $tokens): array
    {
        $metrics = $this->lineCollector->collect($tokens);
        $this->complexityAssigner->assign($tokens, $metrics);

        return $metrics;
    }
}
