<?php

declare(strict_types=1);

namespace Guard\Policy\Limit;

use Guard\Config\Value\LimitConfig;
use Guard\Reporting\MetricViolation;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;

use function sprintf;

/**
 * Builds source metric cyclomatic-complexity violations for function metrics.
 */
final class FunctionComplexityViolationBuilder
{
    /**
     * Returns a complexity violation when the metric exceeds the configured limit.
     */
    public function violation(
        string $relativePath,
        FunctionMetric $metric,
        LimitConfig $limits,
        string $policy = 'standard',
    ): ?MetricViolation {
        $limit = $metric->kind === 'method'
            ? $limits->maxMethodCyclomaticComplexity
            : $limits->maxFunctionCyclomaticComplexity;
        if ($limit === null || $metric->cyclomaticComplexity <= $limit) {
            return null;
        }

        return new MetricViolation(
            $relativePath,
            $metric->startLine,
            'cyclomatic_complexity',
            $metric->cyclomaticComplexity,
            $limit,
            sprintf(
                '%s %s has cyclomatic complexity %d; maximum is %d.',
                $metric->kind,
                $metric->name,
                $metric->cyclomaticComplexity,
                $limit,
            ),
            $policy,
        );
    }
}
