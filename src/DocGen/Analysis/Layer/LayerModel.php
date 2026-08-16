<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Layer;

/**
 * The architectural layers and allowed dependencies of a project.
 *
 * The ruleset maps each layer name to the layer names it may depend on.
 *
 * @property-read list<LayerDefinition> $layers
 * @property-read array<string, list<string>> $ruleset
 */
final class LayerModel
{
    /**
     * @param list<LayerDefinition> $layers
     * @param array<string, list<string>> $ruleset
     */
    public function __construct(
        /** @readonly */
        private array $layers,
        /** @readonly */
        private array $ruleset,
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
            'layers' => $this->layers,
            'ruleset' => $this->ruleset,
            default => null,
        };
    }
}
