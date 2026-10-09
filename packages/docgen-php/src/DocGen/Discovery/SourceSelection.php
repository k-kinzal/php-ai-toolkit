<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Discovery;

/**
 * The package and file selection used only during discovery.
 *
 * @property-read string $root
 * @property-read list<string> $packages
 * @property-read list<string> $vendor
 * @property-read list<string> $exclude
 * @property-read list<string> $vendorDev
 */
final class SourceSelection
{
    /**
     * Creates the package and file selection used only during discovery.
     * @param list<string> $packages
     * @param list<string> $vendor
     * @param list<string> $exclude
     * @param list<string> $vendorDev
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $packages,
        /** @readonly */
        private array $vendor,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private array $vendorDev = [],
    ) {
    }

    /**
     * Provides read-only access to the stage's values.
     *
     * @return mixed the requested value
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'packages' => $this->packages,
            'vendor' => $this->vendor,
            'exclude' => $this->exclude,
            'vendorDev' => $this->vendorDev,
            default => null,
        };
    }
}
