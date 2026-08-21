<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ExceptionHandling;

use function fnmatch;
use function ltrim;

use PhpAiToolkit\PhpStan\Rule\Shared\RulePathNormalizer;

/**
 * Matches analyzed file paths against configured boundary path patterns.
 */
final class BroadCatchPathMatcher
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $patterns;

    /** @readonly */
    private RulePathNormalizer $pathNormalizer;

    /**
     * @param list<string> $patterns fnmatch patterns of boundary files or directories
     */
    public function __construct(array $patterns = [], ?RulePathNormalizer $pathNormalizer = null)
    {
        $this->patterns = $patterns;
        $this->pathNormalizer = $pathNormalizer ?? new RulePathNormalizer();
    }

    /**
     * Reports whether the file is inside the configured boundary layer.
     */
    public function isAllowed(string $filePath): bool
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
