<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Config\LimitConfig;

use function sprintf;

/**
 * Builds LocGuard cyclomatic-complexity violations for function metrics.
 */
final class FunctionComplexityViolationBuilder
{
    /**
     * Returns a complexity violation when the metric exceeds the configured limit.
     */
    public function violation(string $relativePath, FunctionMetric $metric, LimitConfig $limits): ?Violation
    {
        if ($metric->cyclomaticComplexity <= $limits->maxCyclomaticComplexity) {
            return null;
        }

        return new Violation(
            $relativePath,
            $metric->startLine,
            'cyclomatic_complexity',
            $metric->cyclomaticComplexity,
            $limits->maxCyclomaticComplexity,
            sprintf(
                '%s %s has cyclomatic complexity %d; maximum is %d.',
                $metric->kind,
                $metric->name,
                $metric->cyclomaticComplexity,
                $limits->maxCyclomaticComplexity,
            ),
        );
    }
}
