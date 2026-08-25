<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cache;

use function hash;
use function implode;

/**
 * Names the parse result of one source file.
 *
 * Parsing a file reads nothing but that file, so its result is decided by
 * the file's content and by the three facts the collector is told about it:
 * where it sits in the project, which package owns it, and whether it is a
 * dev source. Two files that agree on all four produce the same symbols,
 * which is exactly what makes the result of one reusable for the other.
 */
final class SourceFileKey
{
    /**
     * Returns the cache key of one parsed source file.
     *
     * @param string $fingerprint the fingerprint of the generator
     * @param string $code the complete source of the file
     * @param string $relativeFile the path of the file within the project
     * @param string $packageName the composer package the file belongs to
     * @param bool $isDev whether the file is a dev autoload source
     */
    public function of(string $fingerprint, string $code, string $relativeFile, string $packageName, bool $isDev): string
    {
        return hash('sha256', implode("\0", [
            $fingerprint,
            $relativeFile,
            $packageName,
            $isDev ? 'dev' : 'src',
            hash('sha256', $code),
        ]));
    }
}
