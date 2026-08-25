<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Signature;

use function file_get_contents;
use function hash;
use function is_file;

/**
 * Digests the source files a page was documented from.
 *
 * The documentation of a symbol is decided by the file it is declared in,
 * so the digest of that file answers whether anything the symbol shows
 * could have changed. Files are read at most once per run, because a file
 * that declares one symbol is usually referenced by many pages.
 */
final class SourceDigestIndex
{
    /**
     * Digest of a file the project does not have.
     */
    public const MISSING = 'missing';

    /** @var array<string, string> */
    private array $digests = [];

    /**
     * Returns the digest of one source file of a project.
     *
     * @param string $root the project the file is relative to
     * @param string $relativeFile the path of the file within the project
     */
    public function of(string $root, string $relativeFile): string
    {
        $key = $root . "\0" . $relativeFile;
        if (isset($this->digests[$key])) {
            return $this->digests[$key];
        }

        $path = $root . '/' . $relativeFile;
        $contents = is_file($path) ? @file_get_contents($path) : false;

        return $this->digests[$key] = $contents === false ? self::MISSING : hash('sha256', $contents);
    }
}
