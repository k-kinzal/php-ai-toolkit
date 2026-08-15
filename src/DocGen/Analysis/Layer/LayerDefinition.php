<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Layer;

use function get_object_vars;

/**
 * One deptrac layer with its collector definitions.
 *
 * @property-read string $name
 * @property-read list<LayerCollector> $collectors
 */
final class LayerDefinition
{
    /**
     * @param list<LayerCollector> $collectors
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private array $collectors,
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
