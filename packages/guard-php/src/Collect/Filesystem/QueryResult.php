<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Input\DirectoryListing;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\StructuredFile;
use JsonException;
use RuntimeException;

/**
 * Accumulates one selection while the shared directory queue advances.
 */
final class QueryResult
{
    /** @var array<array-key, FileRecord> */
    private array $files = [];
    /** @var array<string, DirectoryListing> */
    private array $directories = [];
    private RuntimeException|JsonException|\Nette\Neon\Exception|null $failure = null;
    /**
     * Adds a selected path without duplicating overlapping roots.
     */
    public function addFile(FileRecord $file): void
    {
        $this->files[$file->relativePath] = $file;
    }
    /**
     * Adds a filtered directory listing.
     */
    public function addDirectory(DirectoryListing $directory): void
    {
        $this->directories[$directory->relativePath] = $directory;
    }
    /**
     * Keeps the first selection error for deferred policy evaluation.
     */
    public function fail(RuntimeException|JsonException|\Nette\Neon\Exception $failure): void
    {
        $this->failure ??= $failure;
    }
    /**
     * @return array<array-key, FileRecord>
     */
    public function files(): array
    {
        ksort($this->files);
        return $this->files;
    }
    /**
     * Restores selection order after shared file structuring and retains deferred selection errors.
     * @param array<array-key, StructuredFile> $values
     */
    public function fileSet(array $values): FileSet
    {
        $files = [];
        foreach ($this->files() as $key => $file) {
            if (isset($values[$key])) {
                $files[$key] = $values[$key];
            }
        }
        return new FileSet($files, $this->directories(), $this->failure);
    }
    /**
     * @return array<string, DirectoryListing>
     */
    public function directories(): array
    {
        ksort($this->directories);
        return $this->directories;
    }
    /**
     * Returns an error encountered during selection.
     */
    public function failure(): RuntimeException|JsonException|\Nette\Neon\Exception|null
    {
        return $this->failure;
    }
}
