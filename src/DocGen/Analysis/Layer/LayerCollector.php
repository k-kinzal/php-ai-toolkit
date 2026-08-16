<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Layer;

/**
 * One deptrac layer collector definition.
 *
 * @property-read string $type
 * @property-read string $value
 */
final class LayerCollector
{
    /**
     * Creates one collector definition.
     */
    public function __construct(
        /** @readonly */
        private string $type,
        /** @readonly */
        private string $value,
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
            'type' => $this->type,
            'value' => $this->value,
            default => null,
        };
    }
}
