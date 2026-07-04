<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * File-level physical LOC and NCLOC metrics.
 */
final class FileMetric
{
    /**
     * Creates a metric record for one analyzed PHP file.
     */
    public function __construct(
        public readonly string $path,
        public readonly int $physicalLines,
        public readonly int $nonCommentLines,
    ) {
    }
}
