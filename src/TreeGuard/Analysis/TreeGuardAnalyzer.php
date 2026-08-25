<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Analysis;

use function array_merge;

use Toolkit\TreeGuard\Config\TreeGuardConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner;

/**
 * Runs TreeGuard analysis across all configured paths and rules.
 *
 * Every rule whose pattern matches a directory is applied independently;
 * there is no override or merge between overlapping rules.
 */
final class TreeGuardAnalyzer
{
    /** @readonly */
    private DirectoryTreeScanner $scanner;

    /** @readonly */
    private DirectoryPatternMatcher $patternMatcher;

    /** @readonly */
    private DirectoryRuleInspector $ruleInspector;

    /**
     * Creates an analyzer with injectable scanning, matching, and inspection.
     */
    public function __construct(
        ?DirectoryTreeScanner $scanner = null,
        ?DirectoryPatternMatcher $patternMatcher = null,
        ?DirectoryRuleInspector $ruleInspector = null,
    ) {
        $this->scanner = $scanner ?? new DirectoryTreeScanner();
        $this->patternMatcher = $patternMatcher ?? new DirectoryPatternMatcher();
        $this->ruleInspector = $ruleInspector ?? new DirectoryRuleInspector();
    }

    /**
     * Analyzes the configured tree and returns listings and violations.
     */
    public function analyze(TreeGuardConfig $config): AnalysisResult
    {
        $listings = $this->scanner->scan($config);
        $violations = [];

        foreach ($config->rules as $rule) {
            foreach ($listings as $listing) {
                if ($this->patternMatcher->matches($rule->path, $listing->relativePath)) {
                    $violations = array_merge($violations, $this->ruleInspector->inspect($rule, $listing, $listings));
                }
            }
        }

        return new AnalysisResult($listings, $violations);
    }
}
