<?php

declare(strict_types=1);

namespace Guard\Input;

/**
 * One selected path and its filesystem identity.
 *
 * @property-read string $path
 * @property-read string $relativePath
 * @property-read Entry $entry
 */
final class FileRecord
{
    /**
     * Creates the immutable value.
     * @param string $path
     * @param string $relativePath
     * @param Entry $entry
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $relativePath,
        /** @readonly */
        private Entry $entry,
    ) {
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'relativePath' => $this->relativePath,
            'entry' => $this->entry,
            default => null,
        };
    }
}
