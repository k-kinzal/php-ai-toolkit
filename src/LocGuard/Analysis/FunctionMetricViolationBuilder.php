<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_merge;

use PhpAiToolkit\LocGuard\Config\LimitConfig;

/**
 * Builds LocGuard violations for collected function metrics.
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
    public function violations(string $relativePath, array $metrics, LimitConfig $limits): array
    {
        $violations = [];
        foreach ($metrics as $metric) {
            $violations = array_merge($violations, $this->lineViolationBuilder->violations($relativePath, $metric, $limits));
            $complexityViolation = $this->complexityViolationBuilder->violation($relativePath, $metric, $limits);
            if ($complexityViolation !== null) {
                $violations[] = $complexityViolation;
            }
        }

        return $violations;
    }
}
