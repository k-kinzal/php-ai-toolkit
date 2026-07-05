<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function rtrim;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Converts absolute test reporter paths to display paths.
 */
final class TestIssuePathFormatter
{
    /**
     * Creates a path formatter for a project base path.
     */
    public function __construct(
        private readonly string $basePath,
    ) {
    }

    /**
     * Returns a path relative to the configured base path when possible.
     */
    public function relative(string $absolutePath): string
    {
        $base = rtrim($this->basePath, '/') . '/';
        if (str_starts_with($absolutePath, $base)) {
            return substr($absolutePath, strlen($base));
        }

        return $absolutePath;
    }
}
