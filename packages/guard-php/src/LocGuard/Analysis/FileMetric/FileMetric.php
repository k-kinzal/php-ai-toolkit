<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis\FileMetric;

/**
 * File-level physical LOC and NCLOC metrics.
 *
 * @property-read string $path
 * @property-read int $physicalLines
 * @property-read int $nonCommentLines
 */
final class FileMetric
{
    /**
     * Creates a metric record for one analyzed PHP file.
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private int $physicalLines,
        /** @readonly */
        private int $nonCommentLines,
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
            'path' => $this->path,
            'physicalLines' => $this->physicalLines,
            'nonCommentLines' => $this->nonCommentLines,
            default => null,
        };
    }
}
