<?php

declare(strict_types=1);

namespace DecisionsLint\Lint;

use DecisionsLint\Config\Configuration;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Rule\QuestionBuilder;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Source\FileFinder;
use DecisionsLint\Source\GlobMatcher;
use DecisionsLint\Source\Target;
use DecisionsLint\Source\TargetExtractor;

/**
 * Works out which rules apply to which declarations and groups the questions per target.
 *
 * Every rule that judges the same declaration with the same context shares one
 * inquiry, so the model reads the source once and answers every question in
 * one request.
 */
final class Planner
{
    /**
     * Creates a planner, optionally with shared collaborators.
     */
    public function __construct(private ?TargetExtractor $extractor = null, private ?QuestionBuilder $questions = null, private ?GlobMatcher $matcher = null)
    {
    }

    /**
     * Plans a run over the configured paths, or over the given paths, with the enabled rules, or the given rule ids.
     *
     * @param list<string> $paths
     * @param list<string> $ruleIds
     * @throws LintException when a path does not exist, a file cannot be read, or a rule id is unknown
     */
    public function plan(Configuration $configuration, array $paths = [], array $ruleIds = []): Plan
    {
        $rules = $this->rules($configuration, $ruleIds);
        $files = (new FileFinder($configuration->directory, $this->matcher))->find($paths === [] ? $configuration->paths : $paths, $configuration->exclude);
        $groups = [];
        foreach ($files as $file) {
            foreach ($this->candidates($file, $configuration->directory, $this->applicable($rules, $file)) as $candidate) {
                $key = $candidate['target']->file . '|' . $candidate['target']->qualifiedName . '|' . $candidate['target']->line . '|' . $candidate['target']->kind . '|' . $candidate['rule']->context;
                $groups[$key] ??= ['target' => $candidate['target'], 'context' => $candidate['rule']->context, 'rules' => []];
                $groups[$key]['rules'][] = $candidate['rule'];
            }
        }
        $builder = $this->questions ?? new QuestionBuilder();
        $inquiries = [];
        $assignments = [];
        foreach (array_values($groups) as $index => $group) {
            $inquiries[] = $builder->inquiry($group['target'], $group['context'], $group['rules']);
            foreach ($group['rules'] as $rule) {
                $assignments[] = new Assignment($index, $rule, $group['target']);
            }
        }

        return new Plan($files, $inquiries, $assignments);
    }

    /**
     * Returns the rules to run, rejecting an id the configuration does not define.
     *
     * @param list<string> $ruleIds
     * @return list<Rule>
     * @throws LintException when a requested id matches no rule
     */
    public function rules(Configuration $configuration, array $ruleIds): array
    {
        $known = array_map(static fn (Rule $rule): string => $rule->id, $configuration->rules);
        foreach ($ruleIds as $id) {
            if (!in_array($id, $known, true)) {
                throw new LintException(sprintf('Unknown rule "%s". Use one of the configured rule ids: %s.', $id, $known === [] ? '(none)' : implode(', ', $known)));
            }
        }

        return $configuration->activeRules($ruleIds);
    }

    /**
     * Returns the rules whose path globs select a file.
     *
     * @param list<Rule> $rules
     * @return list<Rule>
     */
    public function applicable(array $rules, string $file): array
    {
        $matcher = $this->matcher ?? new GlobMatcher();
        $applicable = [];
        foreach ($rules as $rule) {
            if (($rule->paths === [] || $matcher->matchesAny($rule->paths, $file)) && !$matcher->matchesAny($rule->exclude, $file)) {
                $applicable[] = $rule;
            }
        }

        return $applicable;
    }

    /**
     * Returns every rule-and-target pair of one file.
     *
     * @param list<Rule> $rules
     * @return list<array{rule: Rule, target: Target}>
     * @throws LintException when the file cannot be read
     */
    public function candidates(string $file, string $directory, array $rules): array
    {
        if ($rules === []) {
            return [];
        }
        $path = $directory . '/' . $file;
        $code = is_readable($path) ? file_get_contents($path) : false;
        if ($code === false) {
            throw new LintException(sprintf('Could not read %s. Check the file permissions.', $directory . '/' . $file));
        }
        $extractor = $this->extractor ?? new TargetExtractor();
        $declarations = null;
        $candidates = [];
        foreach ($rules as $rule) {
            if ($rule->target->kind === Target::FILE) {
                $candidates[] = ['rule' => $rule, 'target' => $extractor->file($file, $code)];
                continue;
            }
            if (!str_ends_with(strtolower($file), '.php')) {
                continue;
            }
            $declarations ??= $extractor->extract($file, $code);
            foreach ($declarations as $target) {
                if ($rule->target->selects($target)) {
                    $candidates[] = ['rule' => $rule, 'target' => $target];
                }
            }
        }

        return $candidates;
    }
}
