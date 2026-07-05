<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * Detects token indexes covered by nested function-like metrics.
 */
final class NestedFunctionMetricRange
{
    /**
     * Reports whether the index belongs to a nested metric body.
     *
     * @param list<FunctionMetric> $metrics
     */
    public function contains(int $index, FunctionMetric $metric, array $metrics): bool
    {
        foreach ($metrics as $candidate) {
            if ($candidate === $metric) {
                continue;
            }

            if ($candidate->bodyStartIndex > $metric->bodyStartIndex
                && $candidate->bodyEndIndex < $metric->bodyEndIndex
                && $index >= $candidate->bodyStartIndex
                && $index <= $candidate->bodyEndIndex
            ) {
                return true;
            }
        }

        return false;
    }
}
