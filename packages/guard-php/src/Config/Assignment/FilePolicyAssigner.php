<?php

declare(strict_types=1);

namespace Guard\Config\Assignment;

use function count;

use Guard\Config\Profile\ApplyRuleConfig;
use Guard\Config\Value\MetricsConfig;
use Guard\Policy\PolicyException;

use function implode;
use function sprintf;

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
     * @throws PolicyException when assignment cannot be completed unambiguously
     */
    public function assign(MetricsConfig $config, array $files, bool $requireMatches = true): array
    {
        if ($files === [] && $requireMatches) {
            throw new PolicyException(
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
            if ($count === 0 && $requireMatches) {
                throw new PolicyException(sprintf(
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
     * @throws PolicyException when more than one rule matches the file
     */
    public function assignFile(MetricsConfig $config, string $path, string $relativePath): FilePolicyAssignment
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
            throw new PolicyException(sprintf(
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
