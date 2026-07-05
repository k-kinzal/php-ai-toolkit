<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * Metrics for a function, closure, or method body.
 *
 * @property-read string $kind
 * @property-read string $name
 * @property-read int $startLine
 * @property-read int $endLine
 * @property-read int $bodyStartIndex
 * @property-read int $bodyEndIndex
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
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $name,
        /** @readonly */
        private int $startLine,
        /** @readonly */
        private int $endLine,
        /** @readonly */
        private int $bodyStartIndex,
        /** @readonly */
        private int $bodyEndIndex,
    ) {
    }

    /**
     * Returns the number of physical lines covered by the declaration and body.
     */
    public function lineCount(): int
    {
        return $this->endLine - $this->startLine + 1;
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
