<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cache;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function getmypid;
use function is_array;
use function is_dir;
use function is_file;
use function mkdir;
use function rename;
use function rmdir;
use function scandir;
use function serialize;
use function unlink;
use function unserialize;

/**
 * Reads and writes the cache files of one generation run.
 *
 * A cache is an optimization and never an obligation: a file that cannot be
 * read is read as an empty cache, and a file that cannot be written is
 * reported to the caller rather than thrown, because a run that produced
 * the site is a run that succeeded even when it could not remember it.
 */
final class CacheStore
{
    /**
     * Reads one cache file, or returns an empty cache.
     *
     * Everything this store writes is a serialized array, so anything that
     * does not even start as one was written by something else and is read
     * as no cache at all rather than as a broken one.
     *
     * @return array<mixed>
     */
    public function read(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $contents = @file_get_contents($path);
        if ($contents === false || !str_starts_with($contents, 'a:')) {
            return [];
        }

        $data = @unserialize($contents);

        return is_array($data) ? $data : [];
    }

    /**
     * Writes one cache file, replacing it in a single step.
     *
     * The payload is written next to its destination and renamed onto it,
     * so a run interrupted while writing leaves the previous cache intact
     * instead of a half-written one that the next run would read.
     *
     * @param array<mixed> $data
     *
     * @return bool whether the cache file was written
     */
    public function write(string $path, array $data): bool
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        $temporary = $path . '.' . getmypid() . '.tmp';
        if (@file_put_contents($temporary, serialize($data)) === false) {
            return false;
        }

        if (!@rename($temporary, $path)) {
            @unlink($temporary);

            return false;
        }

        return true;
    }

    /**
     * Makes sure one cache directory exists and can be written to.
     *
     * @return bool whether the directory is there and writable
     */
    public function prepare(string $directory): bool
    {
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        return is_writable($directory);
    }

    /**
     * Removes one cache directory and everything below it.
     */
    public function clear(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            if (is_dir($path)) {
                $this->clear($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
