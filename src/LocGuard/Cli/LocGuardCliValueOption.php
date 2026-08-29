<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Cli;

/**
 * One parsed CLI option carrying a string value.
 *
 * @property-read string $key
 * @property-read string $value
 * @property-read bool $consumesNext
 */
final class LocGuardCliValueOption
{
    /**
     * Creates one normalized value option.
     */
    public function __construct(
        /** @readonly */
        private string $key,
        /** @readonly */
        private string $value,
        /** @readonly */
        private bool $consumesNext,
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
            'key' => $this->key,
            'value' => $this->value,
            'consumesNext' => $this->consumesNext,
            default => null,
        };
    }
}
