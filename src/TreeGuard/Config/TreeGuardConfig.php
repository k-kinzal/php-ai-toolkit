<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Config;

/**
 * Fully resolved TreeGuard configuration.
 *
 * @property-read string $root
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read list<RuleConfig> $rules
 * @property-read ReportConfig $report
 */
final class TreeGuardConfig
{
    /**
     * @param list<string> $paths
     * @param list<string> $exclude
     * @param list<RuleConfig> $rules
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private array $rules,
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
