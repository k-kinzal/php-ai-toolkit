<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Input\Scope;

/**
 * Inputs shared throughout one guard invocation.
 *
 * @property-read string $root
 * @property-read ?Scope $scope
 * @property-read string $configPath
 * @property-read bool $repair
 */
final class Context
{
    /**
     * @param string $root
     * @param string $configPath
     * @param bool $repair
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private string $configPath,
        /** @readonly */
        private bool $repair,
        /** @readonly */
        private ?Scope $scope = null,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'scope' => $this->scope,
            'configPath' => $this->configPath,
            'repair' => $this->repair,
            default => null,
        };
    }
}
