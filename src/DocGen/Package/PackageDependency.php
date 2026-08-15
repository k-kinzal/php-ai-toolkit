<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Package;

use function get_object_vars;

/**
 * One dependency edge between two documented packages.
 *
 * The kind is "require", "require-dev", or "suggest".
 *
 * @property-read string $from
 * @property-read string $to
 * @property-read string $kind
 */
final class PackageDependency
{
    /**
     * Creates one dependency edge.
     */
    public function __construct(
        /** @readonly */
        private string $from,
        /** @readonly */
        private string $to,
        /** @readonly */
        private string $kind,
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
