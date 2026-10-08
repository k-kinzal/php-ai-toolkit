<?php

declare(strict_types=1);

namespace Guard\Input;

use function array_keys;
use function count;
use function explode;
use function fnmatch;
use function trim;

/**
 * Matches anchored file paths with segment-aware star and double-star globs.
 */
final class PathPatternMatcher
{
    /**
     * Reports whether a pattern matches one complete project-relative path.
     */
    public function matches(string $pattern, string $path): bool
    {
        $segments = trim($pattern, '/') === '' ? [] : explode('/', trim($pattern, '/'));
        return isset($this->states($pattern, $path)[count($segments)]);
    }
    /**
     * Reports whether a directory prefix can lead to a matching descendant.
     */
    public function canMatchBelow(string $pattern, string $directory): bool
    {
        $size = trim($pattern, '/') === '' ? 0 : count(explode('/', trim($pattern, '/')));
        foreach (array_keys($this->states($pattern, $directory)) as $position) {
            if ($position < $size) {
                return true;
            }
        }
        return false;
    }
    /**
     * Advances glob states without filesystem access or hidden-file assumptions.
     * @return array<int, true>
     */
    public function states(string $pattern, string $path): array
    {
        $pattern = trim($pattern, '/');
        $path = trim($path, '/');
        $patternSegments = $pattern === '' ? [] : explode('/', $pattern);
        $pathSegments = $path === '' ? [] : explode('/', $path);
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

        return $reachable;
    }
}
