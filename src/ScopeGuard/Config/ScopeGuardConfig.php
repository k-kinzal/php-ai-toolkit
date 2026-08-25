<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Config;

/**
 * Fully resolved ScopeGuard configuration.
 *
 * @property-read string $root
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read list<string> $exemptNamespaces
 * @property-read ReportConfig $report
 */
final class ScopeGuardConfig
{
    /**
     * @param list<string> $paths
     * @param list<string> $exclude
     * @param list<string> $exemptNamespaces
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private array $exemptNamespaces,
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
            'paths' => $this->paths,
            'exclude' => $this->exclude,
            'exemptNamespaces' => $this->exemptNamespaces,
            'report' => $this->report,
            default => null,
        };
    }
}
