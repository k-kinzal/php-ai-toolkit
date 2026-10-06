<?php

declare(strict_types=1);

namespace Guard\Execution;

/**
 * Inputs shared throughout one guard invocation.
 *
 * @property-read \Guard\Config\Configuration $configuration
 * @property-read string $configPath
 * @property-read bool $repair
 */
final class Context
{
    /**
     * @param \Guard\Config\Configuration $configuration
     * @param string $configPath
     * @param bool $repair
     */
    public function __construct(
        /** @readonly */
        private \Guard\Config\Configuration $configuration,
        /** @readonly */
        private string $configPath,
        /** @readonly */
        private bool $repair,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'configuration' => $this->configuration,
            'configPath' => $this->configPath,
            'repair' => $this->repair,
            default => null,
        };
    }
}
