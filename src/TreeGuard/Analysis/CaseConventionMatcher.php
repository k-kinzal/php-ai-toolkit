<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use function preg_match;
use function strpos;
use function substr;

/**
 * Matches file and directory names against configured naming conventions.
 */
final class CaseConventionMatcher
{
    /** @var array<string, string> */
    private const PATTERNS = [
        'pascal' => '/^[A-Z][A-Za-z0-9]*$/',
        'camel' => '/^[a-z][A-Za-z0-9]*$/',
        'snake' => '/^[a-z0-9]+(?:_[a-z0-9]+)*$/',
        'kebab' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
    ];

    /**
     * Reports whether the value follows the given naming convention.
     */
    public function matches(string $convention, string $value): bool
    {
        $pattern = self::PATTERNS[$convention] ?? null;
        if ($pattern === null) {
            return false;
        }

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Returns the file name part before the first dot, empty for dotfiles.
     */
    public function stem(string $fileName): string
    {
        $position = strpos($fileName, '.');
        if ($position === false) {
            return $fileName;
        }

        return substr($fileName, 0, $position);
    }
}
