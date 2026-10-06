<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

/**
 * Cached filesystem facts for one requested path.
 *
 * @property-read bool $directory
 * @property-read bool $file
 * @property-read bool $link
 * @property-read string $identity
 */
final class Entry
{
    /**
     * Creates the immutable value.
     * @param bool $directory
     * @param bool $file
     * @param bool $link
     * @param string $identity
     */
    public function __construct(
        /** @readonly */
        private bool $directory,
        /** @readonly */
        private bool $file,
        /** @readonly */
        private bool $link,
        /** @readonly */
        private string $identity,
    ) {
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'directory' => $this->directory,
            'file' => $this->file,
            'link' => $this->link,
            'identity' => $this->identity,
            default => null,
        };
    }
}
