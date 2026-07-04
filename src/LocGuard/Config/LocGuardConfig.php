<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

/**
 * Fully resolved LocGuard configuration.
 */
final class LocGuardConfig
{
    /**
     * @param list<string> $paths
     * @param list<string> $exclude
     */
    public function __construct(
        public readonly string $root,
        public readonly array $paths,
        public readonly array $exclude,
        public readonly LimitConfig $limits,
        public readonly ReportConfig $report,
    ) {
    }
}
