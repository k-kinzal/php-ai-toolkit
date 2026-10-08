<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Input\Entry;

/**
 * Caches path facts and directory reads across every selection in one invocation.
 */
final class Snapshot
{
    /** @var array<string, Entry> */
    private array $entries = [];
    /** @var array<string, list<string>|false> */
    private array $directories = [];
    /**
     * Creates the Snapshot with its declared dependencies.
     */
    public function __construct(private Filesystem $filesystem)
    {
    }
    /**
     * Returns path facts without repeating stat calls for overlapping selections.
     */
    public function inspect(string $path): Entry
    {
        return $this->entries[$path] ??= $this->filesystem->inspect($path);
    }
    /** Reads a physical directory at most once, including across symlink aliases.
     * @return list<string>|false
     */
    public function entries(string $path): array|false
    {
        $identity = $this->inspect($path)->identity;
        if (!array_key_exists($identity, $this->directories)) {
            $this->directories[$identity] = $this->filesystem->entries($path);
        }
        return $this->directories[$identity];
    }
    /**
     * Reads a selected file; the collector groups all demands before calling this.
     */
    public function read(string $path): string|false
    {
        return $this->filesystem->read($path);
    }
}
