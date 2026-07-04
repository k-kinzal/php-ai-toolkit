<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * Metrics for a class, trait, interface, enum, or anonymous class body.
 */
final class ClassLikeMetric
{
    /**
     * Creates a metric record for one class-like declaration.
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $name,
        public readonly int $startLine,
        public readonly int $endLine,
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
