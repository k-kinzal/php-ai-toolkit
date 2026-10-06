<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

/**
 * An active selection at one directory in the shared walk.
 *
 * @property-read string $query
 * @property-read list<string> $segments
 * @property-read list<int> $positions
 * @property-read list<string> $ancestors
 */
final class Route
{
    /**
     * Creates the immutable value.
     * @param string $query
     * @param list<string> $segments
     * @param list<int> $positions
     * @param list<string> $ancestors
     */
    public function __construct(
        /** @readonly */
        private string $query,
        /** @readonly */
        private array $segments,
        /** @readonly */
        private array $positions,
        /** @readonly */
        private array $ancestors,
    ) {
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'query' => $this->query,
            'segments' => $this->segments,
            'positions' => $this->positions,
            'ancestors' => $this->ancestors,
            default => null,
        };
    }
}
