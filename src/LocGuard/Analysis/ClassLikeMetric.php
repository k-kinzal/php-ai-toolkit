<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * Metrics for a class, trait, interface, enum, or anonymous class body.
 *
 * @property-read string $kind
 * @property-read string $name
 * @property-read int $startLine
 * @property-read int $endLine
 */
final class ClassLikeMetric
{
    /**
     * Creates a metric record for one class-like declaration.
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
    ) {
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

    /**
     * Returns the number of physical lines covered by the declaration and body.
     */
    public function lineCount(): int
    {
        return $this->endLine - $this->startLine + 1;
    }
}
