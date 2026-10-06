<?php

declare(strict_types=1);

namespace Guard\Extension;

use Guard\Policy\Policy;

/** A policy registration, with report order independent of evaluation precedence.
 * @property-read string $id
 * @property-read Policy $policy
 * @property-read int $reportOrder
 */
final class PolicyBinding
{
    /**
     * Creates the PolicyBinding with its declared dependencies.
     */
    public function __construct(
        /** @readonly */
        private string $id,
        /** @readonly */
        private Policy $policy,
        /** @readonly */
        private int $reportOrder = 0,
    ) {
    }
    /**
     * Returns a registered property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'id' => $this->id,
            'policy' => $this->policy,
            'reportOrder' => $this->reportOrder,
            default => null,
        };
    }
}
