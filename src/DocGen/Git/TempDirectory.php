<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Git;

use function is_dir;
use function is_file;
use function is_link;
use function mkdir;

use PhpAiToolkit\DocGen\DocGenException;

use function rmdir;
use function rtrim;
use function scandir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Creates and removes the scratch directories of a diff run.
 *
 * Removal never follows a symbolic link: a checkout links the installed
 * dependencies of the project, and following that link would delete the
 * vendor directory of the working tree.
 */
final class TempDirectory
{
    /**
     * Creates a new empty directory below the system temp directory.
     *
     * @throws DocGenException when the directory cannot be created
     */
    public function create(string $prefix): string
    {
        $path = rtrim(sys_get_temp_dir(), '/') . '/' . $prefix . uniqid('', true);
        if (!@mkdir($path, 0700, true) && !is_dir($path)) {
            throw new DocGenException(sprintf('Failed to create the temporary directory: %s', $path));
        }

        return $path;
    }

    /**
     * Removes one file, link, or directory tree.
     *
     * A link is unlinked instead of followed, and a missing path is not an
     * error, so cleanup after a failed run is always safe to attempt.
     */
    public function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        foreach ($entries === false ? [] : $entries as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->remove($path . '/' . $entry);
            }
        }

        @rmdir($path);
    }
}
