<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Filesystem;

use function count;
use function in_array;
use function is_dir;
use function scandir;
use function sort;
use function str_ends_with;
use function str_starts_with;

/**
 * Finds the Markdown documents of a package directory.
 *
 * Documentation written next to the code is part of what a reader needs, so
 * every Markdown file below the package is collected. Dependency, build, and
 * hidden directories are pruned: they hold documents of other projects or
 * generated output, neither of which belongs to this package.
 */
final class MarkdownFileFinder
{
    /**
     * Directory names that never hold documents of the scanned package.
     *
     * @var list<string>
     */
    public const PRUNED_DIRECTORIES = ['vendor', 'node_modules', 'build', 'dist', 'var'];

    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /** @readonly */
    private SourceFileFinder $sourceFinder;

    /**
     * Creates a Markdown finder with path resolution support.
     */
    public function __construct(?DocGenPathResolver $pathResolver = null, ?SourceFileFinder $sourceFinder = null)
    {
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
        $this->sourceFinder = $sourceFinder ?? new SourceFileFinder();
    }

    /**
     * Finds all Markdown files under a directory, honoring exclude globs.
     *
     * @param list<string> $excludeGlobs
     *
     * @return list<string> absolute file paths in sorted order
     */
    public function find(string $directory, string $projectRoot, array $excludeGlobs): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $queue = [$directory];
        for ($index = 0; $index < count($queue); $index++) {
            $entries = scandir($queue[$index]);
            foreach ($entries === false ? [] : $entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $queue[$index] . '/' . $entry;
                if ($this->sourceFinder->excluded($this->pathResolver->relative($projectRoot, $path), $excludeGlobs)) {
                    continue;
                }

                if (is_dir($path)) {
                    if (!$this->pruned($entry)) {
                        $queue[] = $path;
                    }
                } elseif (str_ends_with($entry, '.md')) {
                    $files[] = $path;
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Reports whether a directory name is pruned from the scan.
     */
    public function pruned(string $name): bool
    {
        return str_starts_with($name, '.') || in_array($name, self::PRUNED_DIRECTORIES, true);
    }
}
