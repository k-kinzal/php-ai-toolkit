<?php

declare(strict_types=1);

namespace Guard\Policy\Limit;

use Guard\Config\Value\LimitConfig;
use Guard\Reporting\MetricViolation;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;

use function sprintf;

/**
 * Builds source metric line-count violations for one function metric.
 */
final class FunctionLineViolationBuilder
{
    /**
     * Returns the line-count violation for the function metric when it exceeds its limit.
     *
     * @return list<MetricViolation>
     */
    public function violations(
        string $relativePath,
        FunctionMetric $metric,
        LimitConfig $limits,
        string $policy = 'standard',
    ): array {
        $limit = $metric->kind === 'method' ? $limits->maxMethodLines : $limits->maxFunctionLines;
        if ($limit === null || $metric->lineCount() <= $limit) {
            return [];
        }

        return [
            new MetricViolation(
                $relativePath,
                $metric->startLine,
                $metric->kind . '_lines',
                $metric->lineCount(),
                $limit,
                sprintf('%s %s has %d physical lines; maximum is %d. Extract unrelated responsibilities into smaller units until each meets the limit, preserving behavior and tests.', $metric->kind, $metric->name, $metric->lineCount(), $limit),
                $policy,
            ),
        ];
    }
}
