<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Filesystem;

use function file_get_contents;
use function is_file;
use function is_readable;
use function sprintf;

use Toolkit\DocGuard\DocGuardException;

/**
 * Reads Markdown documents from disk.
 */
final class MarkdownFileReader
{
    /**
     * Returns the contents of a readable file.
     *
     * @throws DocGuardException when the file cannot be read
     */
    public function read(string $path): string
    {
        $contents = is_file($path) && is_readable($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            throw new DocGuardException(sprintf('Cannot read Markdown document: %s', $path));
        }

        return $contents;
    }
}
