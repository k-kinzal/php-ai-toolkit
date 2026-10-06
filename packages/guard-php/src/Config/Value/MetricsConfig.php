<?php

declare(strict_types=1);

namespace Guard\Config\Value;

use Guard\Config\Profile\ApplyConfig;
use Guard\Config\Profile\PolicyConfig;

/**
 * Fully resolved source metric configuration.
 *
 * @property-read string $root
 * @property-read ScanConfig $scan
 * @property-read array<string, PolicyConfig> $policies
 * @property-read ApplyConfig $apply
 */
final class MetricsConfig
{
    /**
     * @param array<string, PolicyConfig> $policies
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private ScanConfig $scan,
        /** @readonly */
        private array $policies,
        /** @readonly */
        private ApplyConfig $apply,
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
            'root' => $this->root,
            'scan' => $this->scan,
            'policies' => $this->policies,
            'apply' => $this->apply,
            default => null,
        };
    }
}
