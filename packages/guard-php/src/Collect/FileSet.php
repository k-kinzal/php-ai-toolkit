<?php

declare(strict_types=1);

namespace Guard\Collect;

use JsonException;
use RuntimeException;

/**
 * A selected set of files and directory metadata from the shared traversal.
 *
 * @property-read array<array-key, StructuredFile> $files
 * @property-read array<string, DirectoryListing> $directories
 * @property-read RuntimeException|JsonException|\Nette\Neon\Exception|null $failure
 */
final class FileSet
{
    /**
     * Creates the immutable value.
     * @param array<array-key, StructuredFile> $files
     * @param array<string, DirectoryListing> $directories
     * @param RuntimeException|JsonException|\Nette\Neon\Exception|null $failure
     */
    public function __construct(
        /** @readonly */
        private array $files,
        /** @readonly */
        private array $directories,
        /** @readonly */
        private RuntimeException|JsonException|\Nette\Neon\Exception|null $failure,
    ) {
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'files' => $this->files,
            'directories' => $this->directories,
            'failure' => $this->failure,
            default => null,
        };
    }

    /** Raises selection errors before policies inspect individual files.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function validate(): void
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }
}
