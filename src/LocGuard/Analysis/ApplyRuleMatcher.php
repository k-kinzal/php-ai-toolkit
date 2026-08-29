<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis;

use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;

/**
 * Matches one project-relative file path against an application rule.
 */
final class ApplyRuleMatcher
{
    /** @readonly */
    private FilePathPatternMatcher $patternMatcher;

    /**
     * Creates a rule matcher from file path pattern semantics.
     */
    public function __construct(?FilePathPatternMatcher $patternMatcher = null)
    {
        $this->patternMatcher = $patternMatcher ?? new FilePathPatternMatcher();
    }

    /**
     * Reports whether any configured pattern matches the complete file path.
     */
    public function matches(ApplyRuleConfig $rule, string $path): bool
    {
        foreach ($rule->paths as $pattern) {
            if ($this->patternMatcher->matches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
