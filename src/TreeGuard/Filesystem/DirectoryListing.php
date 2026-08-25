<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Filesystem;

/**
 * The direct, exclusion-filtered contents of one scanned directory.
 *
 * @property-read string $relativePath
 * @property-read list<string> $fileNames
 * @property-read list<string> $dirNames
 */
final class DirectoryListing
{
    /**
     * Creates one directory listing with sorted entry names.
     *
     * @param list<string> $fileNames
     * @param list<string> $dirNames
     */
    public function __construct(
        /** @readonly */
        private string $relativePath,
        /** @readonly */
        private array $fileNames,
        /** @readonly */
        private array $dirNames,
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
            'relativePath' => $this->relativePath,
            'fileNames' => $this->fileNames,
            'dirNames' => $this->dirNames,
            default => null,
        };
    }
}
