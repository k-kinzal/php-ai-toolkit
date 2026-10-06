<?php

declare(strict_types=1);

namespace Guard\Collect\Php;

/**
 * Discovered PHP paths and their measured source metrics.
 *
 * @property-read array<string, string> $paths
 * @property-read array<string, SourceMetrics> $metrics
 */
final class PhpSources implements \Guard\Collect\Subject
{
    /**
     * @param array<string, string> $paths
     * @param array<string, SourceMetrics> $metrics
     */
    public function __construct(
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $metrics,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'paths' => $this->paths,
            'metrics' => $this->metrics,
            default => null,
        };
    }
}
