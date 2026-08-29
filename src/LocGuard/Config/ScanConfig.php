<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

/**
 * Source roots and exclusions used to discover PHP files.
 *
 * @property-read list<string> $roots
 * @property-read list<string> $exclude
 */
final class ScanConfig
{
    /**
     * @param list<string> $roots
     * @param list<string> $exclude
     */
    public function __construct(
        /** @readonly */
        private array $roots,
        /** @readonly */
        private array $exclude,
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
            'roots' => $this->roots,
            'exclude' => $this->exclude,
            default => null,
        };
    }
}
