<?php

declare(strict_types=1);

namespace Guard\Collect\Tree\Filesystem;

use Guard\Policy\PolicyException;

use function is_dir;
use function scandir;
use function sort;
use function sprintf;

/**
 * Reads the direct entries of one directory from the filesystem.
 */
final class DirectoryListingReader
{
    /**
     * Returns the sorted direct file and directory names of one directory.
     *
     * @return array{files: list<string>, dirs: list<string>}
     *
     * @throws PolicyException when the directory cannot be read
     */
    public function read(string $absolutePath): array
    {
        if (!is_dir($absolutePath)) {
            throw new PolicyException(sprintf('Failed to read directory: %s', $absolutePath));
        }

        $entries = @scandir($absolutePath);
        if ($entries === false) {
            throw new PolicyException(sprintf('Failed to read directory: %s', $absolutePath));
        }

        $files = [];
        $dirs = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($absolutePath . '/' . $entry)) {
                $dirs[] = $entry;
            } else {
                $files[] = $entry;
            }
        }

        sort($files);
        sort($dirs);

        return ['files' => $files, 'dirs' => $dirs];
    }
}
