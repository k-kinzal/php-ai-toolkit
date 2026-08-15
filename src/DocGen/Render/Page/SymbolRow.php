<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function get_object_vars;

/**
 * One symbol entry of a navigation or index listing.
 *
 * @property-read string $kind
 * @property-read string $name
 * @property-read string $fqcn
 * @property-read string $page
 * @property-read string $summary
 * @property-read list<string> $layers
 * @property-read string $namespace
 */
final class SymbolRow
{
    /**
     * @param list<string> $layers
     */
    public function __construct(
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $fqcn,
        /** @readonly */
        private string $page,
        /** @readonly */
        private string $summary,
        /** @readonly */
        private array $layers,
        /** @readonly */
        private string $namespace = '',
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
