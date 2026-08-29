<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Filesystem;

use function array_keys;
use function count;
use function explode;
use function fnmatch;
use function trim;

/**
 * Matches anchored file paths with segment-aware star and double-star globs.
 */
final class FilePathPatternMatcher
{
    /**
     * Reports whether a pattern matches one complete project-relative path.
     */
    public function matches(string $pattern, string $path): bool
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

        return isset($reachable[count($patternSegments)]);
    }
}
