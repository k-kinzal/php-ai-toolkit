<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config\Policy;

/**
 * Default and path-specific policy assignment configuration.
 *
 * @property-read string $defaultPolicy
 * @property-read list<ApplyRuleConfig> $rules
 */
final class ApplyConfig
{
    /**
     * @param list<ApplyRuleConfig> $rules
     */
    public function __construct(
        /** @readonly */
        private string $defaultPolicy,
        /** @readonly */
        private array $rules,
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
            'defaultPolicy' => $this->defaultPolicy,
            'rules' => $this->rules,
            default => null,
        };
    }
}
