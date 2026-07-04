<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * LocGuard analysis result for one PHP file.
 */
final class FileAnalysis
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(
        public readonly FileMetric $file,
        public readonly array $violations,
    ) {
    }
}
