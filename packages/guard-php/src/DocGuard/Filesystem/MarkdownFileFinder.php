<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Filesystem;

use function array_merge;
use function array_slice;
use function array_unique;
use function array_values;
use function explode;
use function fnmatch;
use function is_dir;
use function is_file;
use function is_link;
use function preg_match;
use function scandir;
use function sort;
use function str_starts_with;

/**
 * Finds the files matching segment-aware path patterns below a root directory.
 *
 * `*` and `?` match within one path segment, and a `**` segment matches zero
 * or more directories. Wildcard segments never match names starting with a
 * dot, and `**` does not follow symbolic links to directories.
 */
final class MarkdownFileFinder
{
    /** @readonly */
    private DocGuardPathResolver $pathResolver;

    /**
     * Creates a finder from path normalization.
     */
    public function __construct(?DocGuardPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new DocGuardPathResolver();
    }

    /**
     * Returns the sorted root-relative paths of the files matching any of the patterns.
     *
     * @param list<string> $patterns
     * @return list<string>
     */
    public function find(string $root, array $patterns): array
    {
        $found = [];
        foreach ($patterns as $pattern) {
            $found = array_merge($found, $this->walk($root, '', explode('/', $this->pathResolver->normalize($pattern))));
        }

        $found = array_values(array_unique($found));
        sort($found);

        return $found;
    }

    /**
     * Returns the files below the root-relative prefix that match the remaining pattern segments.
     *
     * @param list<string> $segments
     * @return list<string>
     */
    public function walk(string $root, string $prefix, array $segments): array
    {
        if ($segments === []) {
            return [];
        }

        $segment = $segments[0];
        $rest = array_slice($segments, 1);
        $directory = $prefix === '' ? $root : $root . '/' . $prefix;
        $entries = is_dir($directory) ? scandir($directory) : false;
        $entries = $entries === false ? [] : $entries;
        $found = [];

        if ($segment === '**') {
            $found = $this->walk($root, $prefix, $rest);
            foreach ($entries as $entry) {
                $child = $prefix === '' ? $entry : $prefix . '/' . $entry;
                if (!str_starts_with($entry, '.') && is_dir($root . '/' . $child) && !is_link($root . '/' . $child)) {
                    $found = array_merge($found, $this->walk($root, $child, $segments));
                }
            }

            return $found;
        }

        $wildcard = preg_match('/[*?\[]/', $segment) === 1;
        foreach ($entries as $entry) {
            $matches = $wildcard ? !str_starts_with($entry, '.') && fnmatch($segment, $entry) : $entry === $segment;
            if (!$matches) {
                continue;
            }

            $child = $prefix === '' ? $entry : $prefix . '/' . $entry;
            if ($rest === []) {
                $found = is_file($root . '/' . $child) ? array_merge($found, [$child]) : $found;
            } elseif (is_dir($root . '/' . $child)) {
                $found = array_merge($found, $this->walk($root, $child, $rest));
            }
        }

        return $found;
    }
}
