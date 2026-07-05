<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Config\LimitConfig;

/**
 * Selects the configured line limit for one class-like metric.
 */
final class ClassLikeMetricLimit
{
    /**
     * Returns the applicable physical-line limit for the class-like kind.
     */
    public function limit(ClassLikeMetric $metric, LimitConfig $limits): int
    {
        if ($metric->kind === 'trait') {
            return $limits->maxTraitLines;
        }

        if ($metric->kind === 'interface') {
            return $limits->maxInterfaceLines;
        }

        if ($metric->kind === 'enum') {
            return $limits->maxEnumLines;
        }

        return $limits->maxClassLines;
    }
}
