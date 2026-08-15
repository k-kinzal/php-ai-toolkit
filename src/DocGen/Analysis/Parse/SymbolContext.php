<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use function get_object_vars;

/**
 * File-level context shared by all symbols built from one source file.
 *
 * @property-read string $namespace
 * @property-read array<string, string> $useMap
 * @property-read string $packageName
 * @property-read string $file
 * @property-read bool $isDev
 */
final class SymbolContext
{
    /**
     * @param array<string, string> $useMap
     */
    public function __construct(
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private array $useMap,
        /** @readonly */
        private string $packageName,
        /** @readonly */
        private string $file,
        /** @readonly */
        private bool $isDev,
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
