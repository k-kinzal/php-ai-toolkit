<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Matches filenames against configured fnmatch exclusion patterns.
 */
final class FilenameExclusionMatcher
{
    /** @var list<string> */
    private readonly array $patterns;

    /**
     * @param list<string> $patterns filename exclusion patterns
     */
    public function __construct(array $patterns = [])
    {
        $this->patterns = $patterns;
    }

    /**
     * Reports whether the basename is excluded.
     */
    public function matches(string $basename): bool
    {
        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $basename)) {
                return true;
            }
        }

        return false;
    }
}
