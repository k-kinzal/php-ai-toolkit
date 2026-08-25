<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cache;

use function filemtime;
use function is_array;
use function is_string;
use function scandir;
use function substr;
use function time;

use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Reference\Usage;

use function touch;
use function unlink;

/**
 * Remembers the parsed symbols of source files between runs.
 *
 * Every file is remembered on its own, under a name that is the file's own
 * content: a run reads back exactly the files it documents, and the worker
 * that would have parsed a file is the worker that reads it instead. That
 * is what keeps a cached run as parallel as an uncached one, which a
 * single cache file for the whole project could not be.
 *
 * Entries nobody read for a while are dropped, so a cache that outlived
 * the branches it was filled from does not grow without end.
 */
final class ParseCache
{
    /**
     * How long an unread entry is kept before it is dropped, in seconds.
     */
    public const RETENTION = 604800;

    /**
     * How old an entry has to be before reading it marks it as read.
     */
    public const TOUCH_AFTER = 86400;

    /**
     * Name of the entry directory below the cache directory.
     */
    public const DIRECTORY = 'sources';

    /** @readonly */
    private string $directory;

    /** @readonly */
    private CacheStore $store;

    private int $reused = 0;

    private int $parsed = 0;

    /**
     * Creates a parse cache below one cache directory.
     */
    public function __construct(string $directory, ?CacheStore $store = null)
    {
        $this->directory = $directory . '/' . self::DIRECTORY;
        $this->store = $store ?? new CacheStore();
    }

    /**
     * Returns the file one entry is kept in.
     *
     * Entries are spread over a level of directories named after the start
     * of their key, because a directory of one entry per source file of a
     * documented dependency tree is a directory nothing enjoys reading.
     */
    public function path(string $key): string
    {
        return $this->directory . '/' . substr($key, 0, 2) . '/' . $key . '.cache';
    }

    /**
     * Returns what is remembered about one file, or null.
     *
     * @return ?array{symbols: FileSymbols|string, usages: list<Usage>}
     */
    public function find(string $key): ?array
    {
        $path = $this->path($key);
        $entry = $this->entry($this->store->read($path));
        if ($entry !== null) {
            $this->keep($path);
        }

        return $entry;
    }

    /**
     * Remembers what one file was parsed into.
     *
     * @param FileSymbols|string $symbols the symbols of the file, or the warning it produced
     * @param list<Usage> $usages the references the file makes
     */
    public function remember(string $key, $symbols, array $usages): void
    {
        $this->store->write($this->path($key), ['symbols' => $symbols, 'usages' => $usages]);
    }

    /**
     * Counts one documented file as read back or as parsed.
     */
    public function counted(bool $reused): void
    {
        if ($reused) {
            $this->reused++;
        } else {
            $this->parsed++;
        }
    }

    /**
     * Marks one entry as read, at most once a day.
     *
     * An entry is kept for as long as it is used, which is what its age
     * says; writing that age on every read would cost more than the entry.
     */
    public function keep(string $path): void
    {
        $modified = @filemtime($path);
        if ($modified !== false && $modified < time() - self::TOUCH_AFTER) {
            @touch($path);
        }
    }

    /**
     * Drops the entries no run has read for a while.
     */
    public function prune(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $oldest = time() - self::RETENTION;
        $shards = scandir($this->directory);
        if ($shards === false) {
            return;
        }

        foreach ($shards as $shard) {
            if ($shard !== '.' && $shard !== '..') {
                $this->pruneShard($this->directory . '/' . $shard, $oldest);
            }
        }
    }

    /**
     * Drops the entries of one shard that are older than one moment.
     */
    public function pruneShard(string $directory, int $oldest): void
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
            $modified = @filemtime($path);
            if ($modified !== false && $modified < $oldest) {
                @unlink($path);
            }
        }
    }

    /**
     * Reads one stored entry as the parse result it must be.
     *
     * Entries come from files that any tool could have written over, so an
     * entry that is not a whole parse result is no entry at all.
     *
     * @param array<mixed> $entry
     *
     * @return ?array{symbols: FileSymbols|string, usages: list<Usage>}
     */
    public function entry(array $entry): ?array
    {
        if (!isset($entry['symbols'], $entry['usages'])) {
            return null;
        }

        $symbols = $entry['symbols'];
        $usages = $entry['usages'];
        if (!is_array($usages) || (!is_string($symbols) && !$symbols instanceof FileSymbols)) {
            return null;
        }

        $references = [];
        foreach ($usages as $usage) {
            if (!$usage instanceof Usage) {
                return null;
            }

            $references[] = $usage;
        }

        return ['symbols' => $symbols, 'usages' => $references];
    }

    /**
     * Returns how many files this run took from the cache.
     */
    public function reused(): int
    {
        return $this->reused;
    }

    /**
     * Returns how many files this run had to parse.
     */
    public function parsed(): int
    {
        return $this->parsed;
    }
}
