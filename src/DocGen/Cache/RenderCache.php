<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cache;

use function filesize;
use function hash;
use function is_array;
use function is_file;
use function is_int;
use function is_string;
use function substr;
use function unlink;

/**
 * Remembers which page of a site was written from which inputs.
 *
 * A page is left alone when everything it was rendered from is unchanged
 * and the file it was written to is still the file this cache wrote. The
 * second half of that condition is what keeps the cache honest: a site
 * somebody emptied, or a page somebody edited, is rendered again rather
 * than declared up to date.
 *
 * One cache serves one output directory, because a page is only up to date
 * relative to the site it belongs to.
 */
final class RenderCache
{
    /**
     * Name prefix of the page cache files below the cache directory.
     */
    public const FILE_PREFIX = 'pages-';

    /** @readonly */
    private string $path;

    /** @readonly */
    private CacheStore $store;

    /** @var array<string, array{signature: string, size: int}> */
    private array $pages = [];

    private int $rendered = 0;

    private int $reused = 0;

    /**
     * Creates the page cache of one output directory.
     */
    public function __construct(string $directory, string $outputRoot, ?CacheStore $store = null)
    {
        $this->path = $directory . '/' . self::FILE_PREFIX . substr(hash('sha256', $outputRoot), 0, 16) . '.cache';
        $this->store = $store ?? new CacheStore();
    }

    /**
     * Reads the pages written by the previous run of this output directory.
     *
     * This is called before the workers are forked, so every worker
     * inherits the pages instead of reading the cache file again.
     */
    public function load(): void
    {
        $data = $this->store->read($this->path);
        $pages = $data['pages'] ?? null;
        if (!is_array($pages)) {
            return;
        }

        foreach ($pages as $path => $page) {
            $valid = $this->page($page);
            if (is_string($path) && $valid !== null) {
                $this->pages[$path] = $valid;
            }
        }
    }

    /**
     * Reports whether one page is already on disk as it would be written.
     *
     * The file is asked about rather than remembered: the answer has to
     * describe the output directory as it is now, so the stat cache of
     * this process is dropped for the file before it is read, and a page
     * something else rewrote is never reported as already written.
     */
    public function isFresh(string $outputRoot, string $path, string $signature): bool
    {
        $page = $this->pages[$path] ?? null;
        if ($page === null || $page['signature'] !== $signature) {
            return false;
        }

        $file = $outputRoot . '/' . $path;
        clearstatcache(true, $file);

        return is_file($file) && @filesize($file) === $page['size'];
    }

    /**
     * Returns the byte count remembered for one page.
     */
    public function sizeOf(string $path): int
    {
        return $this->pages[$path]['size'] ?? 0;
    }

    /**
     * Takes the records of a finished run as the new state of the site.
     *
     * Pages the run did not report are pages the site no longer has, so
     * their files are removed: a site is what this run says it is, not what
     * it says plus whatever an earlier run happened to leave behind.
     *
     * @param list<PageRecord> $records
     *
     * @return bool whether the cache file was written
     */
    public function record(string $outputRoot, array $records): bool
    {
        $pages = [];
        foreach ($records as $record) {
            $pages[$record->path] = ['signature' => $record->signature, 'size' => $record->size];
            if ($record->rendered) {
                $this->rendered++;
            } else {
                $this->reused++;
            }
        }

        foreach ($this->pages as $path => $page) {
            if (!isset($pages[$path])) {
                @unlink($outputRoot . '/' . $path);
            }
        }

        $this->pages = $pages;

        return $this->store->write($this->path, ['pages' => $pages]);
    }

    /**
     * Reads one stored page as the record of a written page it must be.
     *
     * @param mixed $page
     *
     * @return ?array{signature: string, size: int}
     */
    public function page($page): ?array
    {
        if (!is_array($page) || !isset($page['signature'], $page['size'])) {
            return null;
        }

        $signature = $page['signature'];
        $size = $page['size'];

        return is_string($signature) && is_int($size) ? ['signature' => $signature, 'size' => $size] : null;
    }

    /**
     * Returns how many pages this run rendered anew.
     */
    public function rendered(): int
    {
        return $this->rendered;
    }

    /**
     * Returns how many pages this run left as they were.
     */
    public function reused(): int
    {
        return $this->reused;
    }
}
