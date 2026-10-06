<?php

declare(strict_types=1);

namespace Guard\Extension;

/**
 * Associates a policy with an information type.
 *
 * @property-read string $id
 * @property-read class-string<\Guard\Collect\Subject> $informationType
 * @property-read \Guard\Policy\Policy $policy
 */
final class PolicyBinding
{
    /**
     * @param string $id
     * @param class-string<\Guard\Collect\Subject> $informationType
     * @param \Guard\Policy\Policy $policy
     */
    public function __construct(
        /** @readonly */
        private string $id,
        /** @readonly */
        private string $informationType,
        /** @readonly */
        private \Guard\Policy\Policy $policy,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'id' => $this->id,
            'informationType' => $this->informationType,
            'policy' => $this->policy,
            default => null,
        };
    }
}
