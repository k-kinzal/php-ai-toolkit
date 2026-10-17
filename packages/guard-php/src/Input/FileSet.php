<?php

declare(strict_types=1);

namespace Guard\Input;

use JsonException;
use Nette\Neon\Exception;
use RuntimeException;

/**
 * A selected set of files and directory metadata from the shared traversal.
 *
 * @property-read array<array-key, StructuredFile> $files
 * @property-read array<string, DirectoryListing> $directories
 * @property-read RuntimeException|JsonException|Exception|null $failure
 */
final class FileSet
{
    /**
     * Creates the immutable value.
     * @param array<array-key, StructuredFile> $files
     * @param array<string, DirectoryListing> $directories
     * @param RuntimeException|JsonException|Exception|null $failure
     */
    public function __construct(
        /** @readonly */
        private array $files,
        /** @readonly */
        private array $directories,
        /** @readonly */
        private RuntimeException|JsonException|Exception|null $failure,
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
     * @throws Exception
     */
    public function validate(): void
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }
}
