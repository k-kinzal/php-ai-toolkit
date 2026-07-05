<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Config\LimitConfig;

use function sprintf;

/**
 * Builds LocGuard line-count violations for one function metric.
 */
final class FunctionLineViolationBuilder
{
    /**
     * Returns the line-count violation for the function metric when it exceeds its limit.
     *
     * @return list<Violation>
     */
    public function violations(string $relativePath, FunctionMetric $metric, LimitConfig $limits): array
    {
        $limit = $metric->kind === 'method' ? $limits->maxMethodLines : $limits->maxFunctionLines;
        if ($metric->lineCount() <= $limit) {
            return [];
        }

        return [
            new Violation(
                $relativePath,
                $metric->startLine,
                $metric->kind . '_lines',
                $metric->lineCount(),
                $limit,
                sprintf('%s %s has %d physical lines; maximum is %d.', $metric->kind, $metric->name, $metric->lineCount(), $limit),
            ),
        ];
    }
}
