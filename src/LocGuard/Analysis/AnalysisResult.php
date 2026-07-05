<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * Aggregated LocGuard analysis result for a configured project.
 *
 * @property-read list<FileMetric> $files
 * @property-read list<Violation> $violations
 */
final class AnalysisResult
{
    /**
     * @param list<FileMetric> $files
     * @param list<Violation> $violations
     */
    public function __construct(
        /** @readonly */
        private array $files,
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
     * Returns whether any configured threshold was exceeded.
     */
    public function hasViolations(): bool
    {
        return $this->violations !== [];
    }

    /**
     * Returns the number of threshold violations.
     */
    public function violationCount(): int
    {
        return count($this->violations);
    }

    /**
     * Returns the number of analyzed PHP files.
     */
    public function fileCount(): int
    {
        return count($this->files);
    }

    /**
     * Returns the total physical line count across analyzed files.
     */
    public function physicalLineCount(): int
    {
        $total = 0;
        foreach ($this->files as $file) {
            $total += $file->physicalLines;
        }

        return $total;
    }

    /**
     * Returns the total non-comment line count across analyzed files.
     */
    public function nonCommentLineCount(): int
    {
        $total = 0;
        foreach ($this->files as $file) {
            $total += $file->nonCommentLines;
        }

        return $total;
    }
}
