<?php

declare(strict_types=1);

namespace Guard\Policy\Assignment;

use Guard\Input\PathPatternMatcher;
use Guard\Policy\Definition\ApplyRuleConfig;

/**
 * Matches one project-relative file path against an application rule.
 */
final class ApplyRuleMatcher
{
    /** @readonly */
    private PathPatternMatcher $patternMatcher;

    /**
     * Creates a rule matcher from file path pattern semantics.
     */
    public function __construct(?PathPatternMatcher $patternMatcher = null)
    {
        $this->patternMatcher = $patternMatcher ?? new PathPatternMatcher();
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
