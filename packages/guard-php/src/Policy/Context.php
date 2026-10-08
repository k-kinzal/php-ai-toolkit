<?php

declare(strict_types=1);

namespace Guard\Policy;

/**
 * Inputs shared throughout one guard invocation.
 *
 * @property-read string $root
 * @property-read ?\Guard\Input\Scope $scope
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
        private ?\Guard\Input\Scope $scope = null,
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
