<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Input\Entry;

/**
 * Performs the native filesystem operations used by a shared snapshot.
 */
final class NativeFilesystem implements Filesystem
{
    /**
     * Reads path facts and resolves aliases for deduplication.
     */
    public function inspect(string $path): Entry
    {
        $identity = realpath($path);
        return new Entry(is_dir($path), is_file($path), is_link($path), $identity === false ? $path : $identity);
    }
    /**
     * @return list<string>|false
     */
    public function entries(string $path): array|false
    {
        $entries = is_dir($path) && is_readable($path) ? @scandir($path) : false;
        return $entries === false ? false : array_values(array_diff($entries, ['.', '..']));
    }
    /**
     * Reads a file once when the request needs content.
     */
    public function read(string $path): string|false
    {
        return is_file($path) && is_readable($path) ? @file_get_contents($path) : false;
    }
}
