<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

/**
 * The filesystem boundary used by collection, injectable for I/O verification.
 */
interface Filesystem
{
    /**
     * Returns path facts without reading content.
     */
    public function inspect(string $path): Entry;
    /**
     * @return list<string>|false directory entry names, or false when unreadable
     */
    public function entries(string $path): array|false;
    /**
     * Reads the bytes of a selected file.
     */
    public function read(string $path): string|false;
}
