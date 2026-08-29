<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis\FunctionMetric;

use function sprintf;

use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;

/**
 * Builds LocGuard cyclomatic-complexity violations for function metrics.
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
    ): ?Violation {
        $limit = $metric->kind === 'method'
            ? $limits->maxMethodCyclomaticComplexity
            : $limits->maxFunctionCyclomaticComplexity;
        if ($limit === null || $metric->cyclomaticComplexity <= $limit) {
            return null;
        }

        return new Violation(
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
