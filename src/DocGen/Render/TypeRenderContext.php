<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function get_object_vars;

use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;

/**
 * Resolution context for rendering one page's type expressions.
 *
 * The alias map points alias names to page-relative anchors, and the
 * template list holds generic parameter names that are in scope.
 *
 * @property-read string $pagePath
 * @property-read string $namespace
 * @property-read array<string, string> $useMap
 * @property-read list<string> $templates
 * @property-read array<string, string> $aliases
 * @property-read SymbolTable $symbolTable
 */
final class TypeRenderContext
{
    /**
     * @param array<string, string> $useMap
     * @param list<string> $templates
     * @param array<string, string> $aliases
     */
    public function __construct(
        /** @readonly */
        private string $pagePath,
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private array $useMap,
        /** @readonly */
        private array $templates,
        /** @readonly */
        private array $aliases,
        /** @readonly */
        private SymbolTable $symbolTable,
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
