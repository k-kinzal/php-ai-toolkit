<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * LocGuard analysis result for one PHP file.
 *
 * @property-read FileMetric $file
 * @property-read list<Violation> $violations
 */
final class FileAnalysis
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(
        /** @readonly */
        private FileMetric $file,
        /** @readonly */
        private array $violations,
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
