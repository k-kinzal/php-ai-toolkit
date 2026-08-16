<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

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
        $patternCount = count($patternSegments);
        $pathCount = count($pathSegments);
        $patternIndex = 0;
        $pathIndex = 0;
        $starPatternIndex = -1;
        $starPathIndex = 0;

        while ($pathIndex < $pathCount) {
            if ($patternIndex < $patternCount && $patternSegments[$patternIndex] === '**') {
                $starPatternIndex = $patternIndex;
                $starPathIndex = $pathIndex;
                $patternIndex++;
            } elseif ($patternIndex < $patternCount && fnmatch($patternSegments[$patternIndex], $pathSegments[$pathIndex])) {
                $patternIndex++;
                $pathIndex++;
            } elseif ($starPatternIndex >= 0) {
                $patternIndex = $starPatternIndex + 1;
                $starPathIndex++;
                $pathIndex = $starPathIndex;
            } else {
                return false;
            }
        }

        while ($patternIndex < $patternCount && $patternSegments[$patternIndex] === '**') {
            $patternIndex++;
        }

        return $patternIndex === $patternCount;
    }
}
