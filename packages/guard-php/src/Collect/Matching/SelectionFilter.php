<?php

declare(strict_types=1);

namespace Guard\Collect\Matching;

use Guard\Input\PathPatternMatcher;
use Guard\Input\Selection;

/**
 * Applies selection exclusions without touching the filesystem.
 */
final class SelectionFilter
{
    /**
     * Returns whether one recursive candidate survives the configured exclusions.
     */
    public function includes(Selection $selection, string $relative): bool
    {
        foreach ($selection->exclude as $pattern) {
            if ($selection->mode === 'directories') {
                if (fnmatch($pattern, $relative)) {
                    return false;
                }
                continue;
            }
            $parts = explode('/', $relative);
            while ($parts !== []) {
                if ((new PathPatternMatcher())->matches($pattern, implode('/', $parts))) {
                    return false;
                }
                array_pop($parts);
            }
        }
        return true;
    }
}
