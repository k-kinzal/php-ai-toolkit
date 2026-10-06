<?php

declare(strict_types=1);

namespace Guard\Collect\Matching;

/**
 * Advances segment glob states during one directory traversal.
 */
final class GlobMatcher
{
    /** Matches one child name; double stars consume only visible non-symlink directories.
     * @param list<string> $segments
     * @param list<int> $positions
     * @return list<int>
     */
    public function advance(array $segments, array $positions, string $name, bool $directory, bool $link): array
    {
        $active = array_fill_keys($positions, true);
        foreach ($segments as $index => $segment) {
            if (isset($active[$index]) && $segment === '**') {
                $active[$index + 1] = true;
            }
        }
        $next = [];
        foreach ($segments as $index => $segment) {
            if (!isset($active[$index])) {
                continue;
            }
            if ($segment === '**') {
                if ($directory && !$link && !str_starts_with($name, '.')) {
                    $next[$index] = true;
                }
                continue;
            }
            $wildcard = preg_match('/[*?\[]/', $segment) === 1;
            if ($wildcard ? !str_starts_with($name, '.') && fnmatch($segment, $name) : $segment === $name) {
                $next[$index + 1] = true;
            }
        }
        if ($directory) {
            unset($next[count($segments)]);
        }
        return array_keys($next);
    }
}
