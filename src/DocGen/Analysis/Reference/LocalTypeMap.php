<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function strtolower;

/**
 * Tracks statically known variable types inside one function scope.
 *
 * Only assignments whose type is certain are recorded, so method call
 * receivers resolve conservatively instead of guessing.
 */
final class LocalTypeMap
{
    /** @var array<string, string> */
    private array $types = [];

    /**
     * Records the class type of a variable.
     */
    public function set(string $variable, string $fqcn): void
    {
        $this->types[strtolower($variable)] = $fqcn;
    }

    /**
     * Removes a variable whose type became unknown.
     */
    public function forget(string $variable): void
    {
        unset($this->types[strtolower($variable)]);
    }

    /**
     * Returns the class type of a variable, or null when unknown.
     */
    public function typeOf(string $variable): ?string
    {
        return $this->types[strtolower($variable)] ?? null;
    }

    /**
     * Returns all known variable types keyed by lowercased variable name.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->types;
    }
}
