<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Package;

/**
 * Dependency edges between the documented packages.
 *
 * @property-read list<PackageDependency> $edges
 */
final class PackageGraph
{
    /**
     * @param list<PackageDependency> $edges
     */
    public function __construct(
        /** @readonly */
        private array $edges,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'edges' => $this->edges,
            default => null,
        };
    }
}
