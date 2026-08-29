<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

/**
 * One named path rule assigning files to a policy.
 *
 * @property-read string $name
 * @property-read list<string> $paths
 * @property-read string $policy
 */
final class ApplyRuleConfig
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private string $policy,
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
            'paths' => $this->paths,
            'policy' => $this->policy,
            default => null,
        };
    }
}
