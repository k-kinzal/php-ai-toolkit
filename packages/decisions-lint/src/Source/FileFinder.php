<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

use DecisionsLint\Diagnostic\LintException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Lists the files a run reads: everything under the configured paths, minus the excluded globs.
 */
final class FileFinder
{
    /**
     * Creates a finder for one project directory.
     */
    public function __construct(private string $root, private ?GlobMatcher $matcher = null)
    {
    }

    /**
     * Returns the sorted relative paths of the files the paths select and the excludes keep.
     *
     * @param list<string> $paths directories, files or globs relative to the root
     * @param list<string> $exclude globs relative to the root
     * @return list<string>
     * @throws LintException when a path names nothing that exists
     */
    public function find(array $paths, array $exclude): array
    {
        $matcher = $this->matcher ?? new GlobMatcher();
        $found = [];
        foreach ($paths as $pattern) {
            $base = $matcher->base($pattern);
            $absolute = $this->root . ($base === '' ? '' : '/' . $base);
            if (is_file($absolute)) {
                $found[$base] = true;
                continue;
            }
            if (!is_dir($absolute)) {
                throw new LintException(sprintf('Path "%s" does not exist under %s. List existing directories, files or globs in paths.', $pattern, $this->root));
            }
            foreach ($this->walk($absolute) as $relative) {
                if ($matcher->matches($pattern, $relative)) {
                    $found[$relative] = true;
                }
            }
        }
        $files = [];
        foreach (array_keys($found) as $relative) {
            if (!$matcher->matchesAny($exclude, $relative)) {
                $files[] = $relative;
            }
        }
        sort($files);

        return $files;
    }

    /**
     * Returns the relative paths of every regular file below a directory, without following symbolic links.
     *
     * @return list<string>
     */
    public function walk(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || $entry->isLink()) {
                continue;
            }
            $files[] = ltrim(substr($entry->getPathname(), strlen($this->root)), '/');
        }

        return $files;
    }
}
