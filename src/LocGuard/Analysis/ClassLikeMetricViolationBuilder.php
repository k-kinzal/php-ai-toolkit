<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Config\LimitConfig;

use function sprintf;

/**
 * Builds LocGuard violations for class-like metrics.
 */
final class ClassLikeMetricViolationBuilder
{
    /** @readonly */
    private ClassLikeMetricLimit $classLikeMetricLimit;

    /**
     * Creates a builder backed by class-like limit selection.
     */
    public function __construct(
        ?ClassLikeMetricLimit $classLikeMetricLimit = null,
    ) {
        $this->classLikeMetricLimit = $classLikeMetricLimit ?? new ClassLikeMetricLimit();
    }

    /**
     * Returns class, trait, interface, and enum line-count violations.
     *
     * @param list<ClassLikeMetric> $metrics
     * @return list<Violation>
     */
    public function violations(string $relativePath, array $metrics, LimitConfig $limits): array
    {
        $violations = [];
        foreach ($metrics as $metric) {
            $limit = $this->classLikeMetricLimit->limit($metric, $limits);
            if ($metric->lineCount() <= $limit) {
                continue;
            }

            $violations[] = new Violation(
                $relativePath,
                $metric->startLine,
                $metric->kind . '_lines',
                $metric->lineCount(),
                $limit,
                sprintf('%s %s has %d physical lines; maximum is %d.', $metric->kind, $metric->name, $metric->lineCount(), $limit),
            );
        }

        return $violations;
    }
}
