<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function count;

/**
 * Everything one doctest run produced, across every scanned file.
 *
 * @property-read int $fileCount
 * @property-read list<RunResult> $results
 */
final class SuiteResult
{
    /**
     * @param int $fileCount how many source files were scanned
     * @param list<RunResult> $results one entry per example found
     */
    public function __construct(
        /** @readonly */
        private int $fileCount,
        /** @readonly */
        private array $results,
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
            'fileCount' => $this->fileCount,
            'results' => $this->results,
            default => null,
        };
    }

    /**
     * Returns how many examples were found.
     */
    public function total(): int
    {
        return count($this->results);
    }

    /**
     * Returns how many examples ran and passed.
     */
    public function passedCount(): int
    {
        return count($this->results) - $this->failedCount() - $this->skippedCount();
    }

    /**
     * Returns how many examples ran and failed.
     */
    public function failedCount(): int
    {
        return count($this->failed());
    }

    /**
     * Returns how many examples were display-only and never run.
     */
    public function skippedCount(): int
    {
        $skipped = 0;
        foreach ($this->results as $result) {
            $skipped += $result->skipped ? 1 : 0;
        }

        return $skipped;
    }

    /**
     * Returns the results of the examples that failed.
     *
     * @return list<RunResult>
     */
    public function failed(): array
    {
        $failed = [];
        foreach ($this->results as $result) {
            if (!$result->skipped && !$result->passed()) {
                $failed[] = $result;
            }
        }

        return $failed;
    }

    /**
     * Reports whether any example failed.
     */
    public function hasFailures(): bool
    {
        return $this->failed() !== [];
    }
}
