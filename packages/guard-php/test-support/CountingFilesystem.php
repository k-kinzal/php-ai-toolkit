<?php

declare(strict_types=1);

namespace Tests\Support;

use Guard\Collect\Filesystem\Entry;
use Guard\Collect\Filesystem\Filesystem;
use Guard\Collect\Filesystem\NativeFilesystem;

/** Counts real filesystem calls so performance tests verify I/O, not timing guesses. */
final class CountingFilesystem implements Filesystem
{
    /** @var array<string, int> */
    public array $listings = [];
    /** @var array<string, int> */
    public array $reads = [];
    /** @var array<string, int> */
    public array $inspections = [];
    public function inspect(string $path): Entry
    {
        $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
        return (new NativeFilesystem())->inspect($path);
    }
    public function entries(string $path): array|false
    {
        $key = realpath($path);
        $key = $key === false ? $path : $key;
        $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
        return (new NativeFilesystem())->entries($path);
    }
    public function read(string $path): string|false
    {
        $key = realpath($path);
        $key = $key === false ? $path : $key;
        $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
        return (new NativeFilesystem())->read($path);
    }
}
