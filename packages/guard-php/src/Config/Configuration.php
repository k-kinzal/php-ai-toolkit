<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Extension\PolicyBinding;

/** A project root and ordinary policy registrations, without tool-specific compartments.
 * @property-read string $root
 * @property-read list<PolicyBinding> $policies
 */
final class Configuration
{
    /**
     * @param list<PolicyBinding> $policies
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $policies,
    ) {
    }
    /**
     * Returns a declared immutable property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'policies' => $this->policies,
            default => null,
        };
    }
}
