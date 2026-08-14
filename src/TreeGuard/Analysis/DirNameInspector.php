<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use function fnmatch;
use function implode;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;

use function sprintf;

/**
 * Checks direct subdirectory names of one directory against allow, deny, and case rules.
 *
 * Each constraint is applied independently, so one directory can produce
 * several violations. Case checks skip names that start with a dot.
 */
final class DirNameInspector
{
    /** @readonly */
    private CaseConventionMatcher $caseMatcher;

    /**
     * Creates an inspector from naming convention matching.
     */
    public function __construct(?CaseConventionMatcher $caseMatcher = null)
    {
        $this->caseMatcher = $caseMatcher ?? new CaseConventionMatcher();
    }

    /**
     * Returns disallowed_dir, denied_dir, and dir_case violations.
     *
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        foreach ($listing->dirNames as $name) {
            $path = $listing->relativePath . '/' . $name;
            foreach ($rule->denyDirs ?? [] as $pattern) {
                if (fnmatch($pattern, $name)) {
                    $violations[] = new Violation($path, 'denied_dir', $rule->path, null, null, sprintf('Directory "%s" matches denied pattern "%s". Rename or remove it.', $path, $pattern));
                }
            }
            if ($rule->allowDirs !== null && !$this->matchesAny($rule->allowDirs, $name)) {
                $violations[] = new Violation($path, 'disallowed_dir', $rule->path, null, null, sprintf('Directory "%s" does not match any allowed pattern (%s). Rename, move, or delete it.', $path, $rule->allowDirs === [] ? 'none' : implode(', ', $rule->allowDirs)));
            }
            if ($rule->dirCase !== null && !str_starts_with($name, '.') && !$this->caseMatcher->matches($rule->dirCase, $name)) {
                $violations[] = new Violation($path, 'dir_case', $rule->path, null, null, sprintf('Directory name "%s" in "%s" does not follow the %s convention. Rename it.', $name, $listing->relativePath, $rule->dirCase));
            }
        }

        return $violations;
    }

    /**
     * Reports whether the name matches at least one of the glob patterns.
     *
     * @param list<string> $patterns
     */
    public function matchesAny(array $patterns, string $name): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }
}
