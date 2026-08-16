<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

use function fnmatch;
use function implode;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

use function sprintf;

/**
 * Checks direct file names of one directory against allow, deny, and case rules.
 *
 * Each constraint is applied independently, so one file can produce several
 * violations. Case checks use the name part before the first dot and skip
 * dotfiles.
 */
final class FileNameInspector
{
    /** @readonly */
    private CaseConventionMatcher $caseMatcher;

    /** @readonly */
    private TreeGuardPathResolver $pathResolver;

    /**
     * Creates an inspector from naming convention matching and path composition.
     */
    public function __construct(?CaseConventionMatcher $caseMatcher = null, ?TreeGuardPathResolver $pathResolver = null)
    {
        $this->caseMatcher = $caseMatcher ?? new CaseConventionMatcher();
        $this->pathResolver = $pathResolver ?? new TreeGuardPathResolver();
    }

    /**
     * Returns disallowed_file, denied_file, and file_case violations.
     *
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing): array
    {
        $violations = [];
        foreach ($listing->fileNames as $name) {
            $path = $this->pathResolver->child($listing->relativePath, $name);
            foreach ($rule->deny ?? [] as $pattern) {
                if (fnmatch($pattern, $name)) {
                    $violations[] = new Violation($path, 'denied_file', $rule->path, null, null, sprintf('File "%s" matches denied pattern "%s". Rename or remove it.', $path, $pattern));
                }
            }
            if ($rule->allow !== null && !$this->matchesAny($rule->allow, $name)) {
                $violations[] = new Violation($path, 'disallowed_file', $rule->path, null, null, sprintf('File "%s" does not match any allowed pattern (%s). Rename, move, or delete it.', $path, $rule->allow === [] ? 'none' : implode(', ', $rule->allow)));
            }
            if ($rule->fileCase !== null) {
                $stem = $this->caseMatcher->stem($name);
                if ($stem !== '' && !$this->caseMatcher->matches($rule->fileCase, $stem)) {
                    $violations[] = new Violation($path, 'file_case', $rule->path, null, null, sprintf('File name "%s" in "%s" does not follow the %s convention. Rename it.', $name, $listing->relativePath, $rule->fileCase));
                }
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
