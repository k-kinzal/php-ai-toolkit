<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Filesystem;

use function count;
use function fnmatch;
use function is_dir;
use function scandir;
use function sort;
use function str_ends_with;

/**
 * Finds PHP source files below PSR-4 autoload directories.
 *
 * The scan uses an explicit queue and prunes excluded directories, so an
 * excluded subtree is never entered.
 */
final class SourceFileFinder
{
    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /**
     * Creates a source file finder with path resolution support.
     */
    public function __construct(?DocGenPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
    }

    /**
     * Finds all PHP files under a directory, honoring exclude globs.
     *
     * Exclude globs are matched against the path relative to the project
     * root, mirroring the other toolkit CLIs.
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
            $current = $queue[$index];
            $entries = scandir($current);
            foreach ($entries === false ? [] : $entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $current . '/' . $entry;
                if ($this->excluded($this->pathResolver->relative($projectRoot, $path), $excludeGlobs)) {
                    continue;
                }

                if (is_dir($path)) {
                    $queue[] = $path;
                } elseif (str_ends_with($entry, '.php')) {
                    $files[] = $path;
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Reports whether a relative path matches one of the exclude globs.
     *
     * @param list<string> $excludeGlobs
     */
    public function excluded(string $relativePath, array $excludeGlobs): bool
    {
        foreach ($excludeGlobs as $glob) {
            if (fnmatch($glob, $relativePath)) {
                return true;
            }
        }

        return false;
    }
}
