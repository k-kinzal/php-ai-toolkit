<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

use Toolkit\LocGuard\Config\LimitConfig;

/**
 * Fully resolved source metric policy.
 *
 * @property-read string $name
 * @property-read ?string $extends
 * @property-read LimitConfig $limits
 */
final class PolicyConfig
{
    /**
     * Creates a named policy with effective limits.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private ?string $extends,
        /** @readonly */
        private LimitConfig $limits,
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
            'limits' => $this->limits,
            default => null,
        };
    }
}
