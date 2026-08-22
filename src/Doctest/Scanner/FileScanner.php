<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Scanner;

use function fnmatch;
use function is_dir;
use function is_file;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * Scans directories and files for PHP source files based on configuration.
 *
 * FileScanner iterates through configured directories and files, filtering
 * out excluded patterns, to produce a list of PHP files that should be
 * processed for doctest examples.
 */
final class FileScanner
{
    /**
     * @param Configuration $config the configuration specifying directories and exclusions
     */
    public function __construct(
        /** @readonly */
        private Configuration $config,
    ) {
    }

    /**
     * Scans all configured sources and yields PHP file paths.
     *
     * Iterates through configured directories recursively, as well as
     * individual files, filtering based on exclude patterns.
     *
     * @return iterable<string> absolute paths to PHP files
     */
    public function scan(): iterable
    {
        foreach ($this->config->getDirectories() as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            yield from $this->scanDirectory($directory);
        }

        foreach ($this->config->getFiles() as $file) {
            if (is_file($file) && $this->shouldInclude($file)) {
                yield $file;
            }
        }
    }

    /**
     * Yields the PHP files below one directory.
     *
     * @return iterable<string>
     */
    public function scanDirectory(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var RegexIterator<int, SplFileInfo, RecursiveIteratorIterator<RecursiveDirectoryIterator>> $phpFiles */
        $phpFiles = new RegexIterator($iterator, '/\.php$/');

        $paths = [];
        foreach ($phpFiles as $file) {
            $path = $file->getPathname();
            if ($this->shouldInclude($path)) {
                $paths[] = $path;
            }
        }

        sort($paths);

        yield from $paths;
    }

    /**
     * Reports whether a discovered path survives the configured exclude patterns.
     */
    public function shouldInclude(string $path): bool
    {
        foreach ($this->config->getExcludePatterns() as $pattern) {
            if (fnmatch($pattern, $path) || fnmatch('*' . $pattern, $path)) {
                return false;
            }
        }

        return true;
    }
}
