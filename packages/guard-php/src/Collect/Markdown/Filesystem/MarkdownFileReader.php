<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown\Filesystem;

use function file_get_contents;

use Guard\Policy\PolicyException;

use function is_file;
use function is_readable;
use function sprintf;

/**
 * Reads Markdown documents from disk.
 */
final class MarkdownFileReader
{
    /**
     * Returns the contents of a readable file.
     *
     * @throws PolicyException when the file cannot be read
     */
    public function read(string $path): string
    {
        $contents = is_file($path) && is_readable($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            throw new PolicyException(sprintf('Cannot read Markdown document: %s', $path));
        }

        return $contents;
    }
}
