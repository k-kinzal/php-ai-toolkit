<?php

declare(strict_types=1);

namespace Guard\Policy\Loc\FunctionMetric;

use function array_merge;

use Guard\Collect\Php\FunctionMetric\FunctionMetric;
use Guard\Config\Loc\LimitConfig;
use Guard\Policy\Loc\Violation;

/**
 * Builds source metric violations for collected function metrics.
 */
final class FunctionMetricViolationBuilder
{
    /** @readonly */
    private FunctionLineViolationBuilder $lineViolationBuilder;

    /** @readonly */
    private FunctionComplexityViolationBuilder $complexityViolationBuilder;

    /**
     * Creates a builder from function line and complexity violation builders.
     */
    public function __construct(
        ?FunctionLineViolationBuilder $lineViolationBuilder = null,
        ?FunctionComplexityViolationBuilder $complexityViolationBuilder = null,
    ) {
        $this->lineViolationBuilder = $lineViolationBuilder ?? new FunctionLineViolationBuilder();
        $this->complexityViolationBuilder = $complexityViolationBuilder ?? new FunctionComplexityViolationBuilder();
    }

    /**
     * Returns line-count and complexity violations for function metrics.
     *
     * @param list<FunctionMetric> $metrics
     * @return list<Violation>
     */
    public function violations(string $relativePath, array $metrics, LimitConfig $limits, string $policy = 'standard'): array
    {
        $violations = [];
        foreach ($metrics as $metric) {
            $violations = array_merge($violations, $this->lineViolationBuilder->violations($relativePath, $metric, $limits, $policy));
            $complexityViolation = $this->complexityViolationBuilder->violation($relativePath, $metric, $limits, $policy);
            if ($complexityViolation !== null) {
                $violations[] = $complexityViolation;
            }
        }

        return $violations;
    }
}
