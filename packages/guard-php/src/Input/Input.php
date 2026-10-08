<?php

declare(strict_types=1);

namespace Guard\Input;

/**
 * Associates a file selection with the structure needed by a policy.
 *
 * @property-read Selection $selection
 * @property-read ?string $structure
 */
final class Input
{
    /**
     * Creates the immutable value.
     * @param Selection $selection
     * @param ?string $structure
     */
    public function __construct(
        /** @readonly */
        private Selection $selection,
        /** @readonly */
        private ?string $structure = null,
    ) {
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'selection' => $this->selection,
            'structure' => $this->structure,
            default => null,
        };
    }
}
