<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use function count;

use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;

/**
 * Aggregated TreeGuard analysis result for a configured project.
 *
 * @property-read array<string, DirectoryListing> $listings
 * @property-read list<Violation> $violations
 */
final class AnalysisResult
{
    /**
     * @param array<string, DirectoryListing> $listings
     * @param list<Violation> $violations
     */
    public function __construct(
        /** @readonly */
        private array $listings,
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

    /**
     * Returns whether any configured constraint was violated.
     */
    public function hasViolations(): bool
    {
        return $this->violations !== [];
    }

    /**
     * Returns the number of structure violations.
     */
    public function violationCount(): int
    {
        return count($this->violations);
    }

    /**
     * Returns the number of scanned directories.
     */
    public function directoryCount(): int
    {
        return count($this->listings);
    }

    /**
     * Returns the total number of scanned files across all directories.
     */
    public function fileCount(): int
    {
        $total = 0;
        foreach ($this->listings as $listing) {
            $total += count($listing->fileNames);
        }

        return $total;
    }
}
