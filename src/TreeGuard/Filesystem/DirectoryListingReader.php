<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Filesystem;

use function is_dir;

use PhpAiToolkit\TreeGuard\TreeGuardException;

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
     * @throws TreeGuardException when the directory cannot be read
     */
    public function read(string $absolutePath): array
    {
        if (!is_dir($absolutePath)) {
            throw new TreeGuardException(sprintf('Failed to read directory: %s', $absolutePath));
        }

        $entries = @scandir($absolutePath);
        if ($entries === false) {
            throw new TreeGuardException(sprintf('Failed to read directory: %s', $absolutePath));
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
