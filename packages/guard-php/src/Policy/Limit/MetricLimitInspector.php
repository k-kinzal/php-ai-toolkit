<?php

declare(strict_types=1);

namespace Guard\Policy\Limit;

use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Diagnostic\MetricViolation;
use Guard\Structure\Php\SourceMetrics;

/**
 * Evaluates numeric limits on already-measured source.
 */
final class MetricLimitInspector
{
    /**
     * @return list<MetricViolation>
     */
    public function violations(SourceMetrics $metrics, LimitConfig $limits, string $profile = 'standard'): array
    {
        if (!$metrics->readable) {
            return [];
        }
        return array_merge(
            (new FileMetricViolationBuilder())->violations($metrics->file, $limits, $profile),
            (new ClassLikeMetricViolationBuilder())->violations($metrics->file->path, $metrics->classes, $limits, $profile),
            (new FunctionMetricViolationBuilder())->violations($metrics->file->path, $metrics->functions, $limits, $profile),
        );
    }
}
