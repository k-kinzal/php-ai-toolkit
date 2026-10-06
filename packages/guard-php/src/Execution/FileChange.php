<?php

declare(strict_types=1);

namespace Guard\Execution;

/**
 * A validated replacement with the original content retained for concurrency checks.
 *
 * @property-read string $path
 * @property-read string $original
 * @property-read string $replacement
 */
final class FileChange
{
    /**
     * Creates the immutable value.
     * @param string $path
     * @param string $original
     * @param string $replacement
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $original,
        /** @readonly */
        private string $replacement,
    ) {
    }

    /** Returns a declared immutable property.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'original' => $this->original,
            'replacement' => $this->replacement,
            default => null,
        };
    }
}
