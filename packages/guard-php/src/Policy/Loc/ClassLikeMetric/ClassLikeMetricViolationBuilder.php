<?php

declare(strict_types=1);

namespace Guard\Policy\Loc\ClassLikeMetric;

use Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Config\Loc\LimitConfig;
use Guard\Policy\Loc\Violation;

use function sprintf;

/**
 * Builds source metric violations for class-like metrics.
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
    public function violations(string $relativePath, array $metrics, LimitConfig $limits, string $policy = 'standard'): array
    {
        $violations = [];
        foreach ($metrics as $metric) {
            $limit = $this->classLikeMetricLimit->limit($metric, $limits);
            if ($limit === null || $metric->lineCount() <= $limit) {
                continue;
            }

            $violations[] = new Violation(
                $relativePath,
                $metric->startLine,
                $metric->kind . '_lines',
                $metric->lineCount(),
                $limit,
                sprintf('%s %s has %d physical lines; maximum is %d.', $metric->kind, $metric->name, $metric->lineCount(), $limit),
                $policy,
            );
        }

        return $violations;
    }
}
