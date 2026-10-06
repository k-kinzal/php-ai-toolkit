<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Extension\PolicyBinding;

/** A project root and ordinary policy registrations, without tool-specific compartments.
 * @property-read string $root
 * @property-read list<PolicyBinding> $policies
 * @property-read array<string, array<string, mixed>> $extensions
 * @property-read ?\Guard\Collect\Scope $scope
 */
final class Configuration
{
    /**
     * @param list<PolicyBinding> $policies
     * @param array<string, array<string, mixed>> $extensions
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $policies,
        /** @readonly */
        public array $extensions = [],
        /** @readonly */
        private ?\Guard\Collect\Scope $scope = null,
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
            'scope' => $this->scope,
            default => null,
        };
    }
}
