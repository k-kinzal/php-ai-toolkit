<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

/**
 * Fully resolved LocGuard configuration.
 *
 * @property-read string $root
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read LimitConfig $limits
 * @property-read ReportConfig $report
 */
final class LocGuardConfig
{
    /**
     * @param list<string> $paths
     * @param list<string> $exclude
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private LimitConfig $limits,
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
        return get_object_vars($this)[$name] ?? null;
    }
}
