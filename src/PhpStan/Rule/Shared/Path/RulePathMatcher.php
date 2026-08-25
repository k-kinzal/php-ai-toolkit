<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared\Path;

use function fnmatch;
use function ltrim;

/**
 * Matches analyzed files against configured rule exemption patterns.
 */
final class RulePathMatcher
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $patterns;

    /** @readonly */
    private RulePathNormalizer $pathNormalizer;

    /**
     * @param list<string> $patterns fnmatch patterns of files or directories
     */
    public function __construct(array $patterns = [], ?RulePathNormalizer $pathNormalizer = null)
    {
        $this->patterns = $patterns;
        $this->pathNormalizer = $pathNormalizer ?? new RulePathNormalizer();
    }

    /**
     * Reports whether the file matches one configured pattern.
     */
    public function matches(string $filePath): bool
    {
        $path = $this->pathNormalizer->normalize($filePath);
        foreach ($this->patterns as $pattern) {
            $normalizedPattern = ltrim($this->pathNormalizer->normalize($pattern), '/');
            if (fnmatch($normalizedPattern, $path) || fnmatch('*/' . $normalizedPattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
