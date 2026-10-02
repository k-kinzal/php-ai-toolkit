<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

/**
 * Unresolved policy definition read directly from loc.yaml.
 *
 * @property-read string $name
 * @property-read ?string $extends
 * @property-read array<string, ?int> $limitPatch
 */
final class PolicyDefinition
{
    /**
     * @param array<string, ?int> $limitPatch
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private ?string $extends,
        /** @readonly */
        private array $limitPatch,
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
            'name' => $this->name,
            'extends' => $this->extends,
            'limitPatch' => $this->limitPatch,
            default => null,
        };
    }
}
