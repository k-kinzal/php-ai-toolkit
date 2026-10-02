<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ExceptionHandling;

use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;

/**
 * Matches analyzed file paths against configured boundary path patterns.
 */
final class BroadCatchPathMatcher
{
    /** @readonly */
    private RulePathMatcher $pathMatcher;

    /**
     * @param list<string> $patterns fnmatch patterns of boundary files or directories
     */
    public function __construct(array $patterns = [], ?RulePathNormalizer $pathNormalizer = null)
    {
        $this->pathMatcher = new RulePathMatcher($patterns, $pathNormalizer);
    }

    /**
     * Reports whether the file is inside the configured boundary layer.
     */
    public function isAllowed(string $filePath): bool
    {
        return $this->pathMatcher->matches($filePath);
    }
}
