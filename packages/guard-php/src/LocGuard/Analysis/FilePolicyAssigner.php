<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis;

use function count;
use function implode;
use function sprintf;

use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\LocGuardException;

/**
 * Assigns exactly one effective metric policy to every discovered file.
 */
final class FilePolicyAssigner
{
    /** @readonly */
    private ApplyRuleMatcher $ruleMatcher;

    /**
     * Creates an assigner from path rule matching.
     */
    public function __construct(?ApplyRuleMatcher $ruleMatcher = null)
    {
        $this->ruleMatcher = $ruleMatcher ?? new ApplyRuleMatcher();
    }

    /**
     * Assigns policies and rejects empty, ambiguous, or stale configured scopes.
     *
     * @param array<string, string> $files map of absolute path to project-relative path
     * @return list<FilePolicyAssignment>
     *
     * @throws LocGuardException when assignment cannot be completed unambiguously
     */
    public function assign(LocGuardConfig $config, array $files): array
    {
        if ($files === []) {
            throw new LocGuardException(
                'Configured scan roots contain no PHP files. Set scan.roots to production source directories.',
            );
        }

        $matchCounts = [];
        foreach ($config->apply->rules as $rule) {
            $matchCounts[$rule->name] = 0;
        }

        $assignments = [];
        foreach ($files as $path => $relativePath) {
            $assignment = $this->assignFile($config, $path, $relativePath);
            if ($assignment->rule !== null) {
                $matchCounts[$assignment->rule]++;
            }
            $assignments[] = $assignment;
        }

        foreach ($matchCounts as $ruleName => $count) {
            if ($count === 0) {
                throw new LocGuardException(sprintf(
                    'Apply rule "%s" matches no scanned PHP files. Fix or remove its path patterns.',
                    $ruleName,
                ));
            }
        }

        return $assignments;
    }

    /**
     * Assigns one file and rejects overlapping path rules.
     *
     * @throws LocGuardException when more than one rule matches the file
     */
    public function assignFile(LocGuardConfig $config, string $path, string $relativePath): FilePolicyAssignment
    {
        $matched = [];
        foreach ($config->apply->rules as $rule) {
            if ($this->ruleMatcher->matches($rule, $relativePath)) {
                $matched[] = $rule;
            }
        }

        if (count($matched) > 1) {
            $names = [];
            foreach ($matched as $rule) {
                $names[] = '"' . $rule->name . '"';
            }
            throw new LocGuardException(sprintf(
                'File "%s" matches multiple apply rules: %s. Make their path patterns disjoint.',
                $relativePath,
                implode(', ', $names),
            ));
        }

        /** @var ?ApplyRuleConfig $rule */
        $rule = $matched[0] ?? null;
        $policyName = $rule === null ? $config->apply->defaultPolicy : $rule->policy;

        return new FilePolicyAssignment(
            $path,
            $relativePath,
            $config->policies[$policyName],
            $rule === null ? null : $rule->name,
        );
    }
}
