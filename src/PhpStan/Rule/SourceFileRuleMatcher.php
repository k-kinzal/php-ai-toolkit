<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Matches files that belong to a configured source directory marker.
 */
final class SourceFileRuleMatcher
{
    /**
     * Reports whether the file path is inside the source marker.
     */
    public function isSourceFile(string $file, string $srcMarker): bool
    {
        return strpos($file, $srcMarker) !== false;
    }
}
