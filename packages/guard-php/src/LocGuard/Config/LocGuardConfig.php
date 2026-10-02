<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Config;

use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;

/**
 * Fully resolved LocGuard configuration.
 *
 * @property-read string $root
 * @property-read ScanConfig $scan
 * @property-read array<string, PolicyConfig> $policies
 * @property-read ApplyConfig $apply
 * @property-read ReportConfig $report
 */
final class LocGuardConfig
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
        /** @readonly */
        private ReportConfig $report,
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
            'report' => $this->report,
            default => null,
        };
    }
}
