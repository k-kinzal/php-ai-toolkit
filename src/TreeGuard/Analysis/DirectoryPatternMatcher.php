<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Analysis;

use function array_keys;
use function count;
use function explode;
use function fnmatch;
use function trim;

/**
 * Matches rule path patterns against relative directory paths segment by segment.
 *
 * A "**" segment matches zero or more path segments, so "src/**" also matches
 * "src" itself. Every other segment is matched with fnmatch against exactly
 * one path segment, so "*" never crosses a "/" boundary. Patterns are
 * anchored at both ends.
 *
 * The project root is the path "." and carries no segment at all, so only "."
 * and "**" match it; "*" matches the directories directly below it instead.
 */
final class DirectoryPatternMatcher
{
    /**
     * Reports whether the pattern matches the whole relative directory path.
     */
    public function matches(string $pattern, string $path): bool
    {
        $pattern = trim($pattern, '/');
        $path = trim($path, '/');
        $patternSegments = $pattern === '' || $pattern === '.' ? [] : explode('/', $pattern);
        $pathSegments = $path === '' || $path === '.' ? [] : explode('/', $path);
        $reachable = [0 => true];

        foreach ($pathSegments as $pathSegment) {
            foreach (array_keys($patternSegments) as $patternIndex) {
                if (isset($reachable[$patternIndex]) && $patternSegments[$patternIndex] === '**') {
                    $reachable[$patternIndex + 1] = true;
                }
            }

            $next = [];
            foreach ($patternSegments as $patternIndex => $patternSegment) {
                if (!isset($reachable[$patternIndex])) {
                    continue;
                }

                if ($patternSegment === '**') {
                    $next[$patternIndex] = true;
                } elseif (fnmatch($patternSegment, $pathSegment)) {
                    $next[$patternIndex + 1] = true;
                }
            }

            $reachable = $next;
        }

        foreach (array_keys($patternSegments) as $patternIndex) {
            if (isset($reachable[$patternIndex]) && $patternSegments[$patternIndex] === '**') {
                $reachable[$patternIndex + 1] = true;
            }
        }

        return isset($reachable[count($patternSegments)]);
    }
}
