<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Package;

use function get_object_vars;

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
        return get_object_vars($this)[$name] ?? null;
    }
}
