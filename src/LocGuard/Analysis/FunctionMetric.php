<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * Metrics for a function, closure, or method body.
 */
final class FunctionMetric
{
    /**
     * Current cyclomatic complexity calculated for this function-like body.
     */
    public int $cyclomaticComplexity = 1;

    /**
     * Creates a metric record for one function, closure, or method body.
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $name,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly int $bodyStartIndex,
        public readonly int $bodyEndIndex,
    ) {
    }

    /**
     * Returns the number of physical lines covered by the declaration and body.
     */
    public function lineCount(): int
    {
        return $this->endLine - $this->startLine + 1;
    }
}
