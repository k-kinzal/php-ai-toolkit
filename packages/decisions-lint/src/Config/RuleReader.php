<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;

/**
 * Turns one merged rule definition into a Rule, checking what the schema cannot: that the parts fit each other.
 */
final class RuleReader
{
    /**
     * Creates a reader, optionally with a shared template checker.
     */
    public function __construct(private ?Template $template = null)
    {
    }

    /**
     * Builds the rule with an id from its merged definition.
     *
     * @param array<string, mixed> $definition
     * @throws LintException when a required part is missing or the parts contradict each other
     */
    public function read(string $id, array $definition): Rule
    {
        $context = sprintf('Rule "%s" (%s)', $id, implode(', ', $this->strings($definition['source'] ?? null)));
        foreach (['target', 'decision', 'violations'] as $key) {
            if (!array_key_exists($key, $definition)) {
                throw new LintException(sprintf('%s has no %s. Add it, or import the rule file that defines the rule before overriding it in decisions.yaml.', $context, $key));
            }
        }
        $decision = $this->decision($this->mapping($definition['decision']), $context);
        $violations = [];
        foreach ($this->list($definition['violations']) as $index => $violation) {
            $violations[] = $this->violation($this->mapping($violation), $decision, $context . '.violations[' . $index . ']');
        }
        $severity = $definition['severity'] ?? Severity::ERROR;
        $contextMode = $definition['context'] ?? 'class';

        return new Rule(
            $id,
            is_string($definition['description'] ?? null) ? $definition['description'] : '',
            is_string($severity) ? $severity : Severity::ERROR,
            ($definition['enabled'] ?? true) !== false,
            $this->strings($definition['paths'] ?? null),
            $this->strings($definition['exclude'] ?? null),
            $this->target($this->mapping($definition['target'])),
            is_string($contextMode) ? $contextMode : 'class',
            $decision,
            $violations,
        );
    }

    /**
     * Builds the selector of a rule.
     *
     * @param array<string, mixed> $target
     */
    public function target(array $target): TargetSelector
    {
        $kind = $target['kind'] ?? 'file';
        $static = $target['static'] ?? null;

        return new TargetSelector(is_string($kind) ? $kind : 'file', $this->strings($target['visibility'] ?? null), $this->strings($target['names'] ?? null), $this->strings($target['exclude_names'] ?? null), is_bool($static) ? $static : null);
    }

    /**
     * Builds the decision of a rule, requiring the options a choice or the levels a score needs.
     *
     * @param array<string, mixed> $decision
     * @throws LintException when the type is missing, the instructions are missing, or the type lacks its options or levels
     */
    public function decision(array $decision, string $context): DecisionSpec
    {
        $type = $decision['type'] ?? null;
        if (!is_string($type)) {
            throw new LintException(sprintf('%s: decision.type is missing. Set it to predicate, choice or score.', $context));
        }
        $instructions = $decision['instructions'] ?? null;
        if (!is_string($instructions) || trim($instructions) === '') {
            throw new LintException(sprintf('%s: decision.instructions is missing. Write the question the model answers about each target.', $context));
        }
        $template = $this->template ?? new Template();
        $template->check($instructions, $context . ': decision.instructions');
        $choices = [];
        foreach ($this->mapping($decision['choices'] ?? null) as $name => $description) {
            $choices[$name] = is_string($description) ? $description : '';
        }
        $levels = $this->strings($decision['levels'] ?? null);
        if ($type === Question::CHOICE && count($choices) < 2) {
            throw new LintException(sprintf('%s: decision.choices must list at least 2 options when decision.type is choice. Add the options as name: description pairs.', $context));
        }
        if ($type === Question::SCORE && count($levels) < 2) {
            throw new LintException(sprintf('%s: decision.levels must list at least 2 levels when decision.type is score. Add the level descriptions from lowest to highest.', $context));
        }
        $threshold = $decision['threshold'] ?? 0.5;

        return new DecisionSpec($type, $instructions, $choices, $levels, is_int($threshold) || is_float($threshold) ? (float) $threshold : 0.5);
    }

    /**
     * Builds one violation, requiring the answer form the decision type uses.
     *
     * @param array<string, mixed> $violation
     * @throws LintException when the answer does not fit the decision type or a template is invalid
     */
    public function violation(array $violation, DecisionSpec $decision, string $context): Violation
    {
        $answer = $violation['answer'] ?? null;
        $minScore = $violation['min_score'] ?? null;
        $maxScore = $violation['max_score'] ?? null;
        $this->checkAnswer($decision, $answer, $minScore === null && $maxScore === null, $context);
        $message = $violation['message'] ?? null;
        $fix = $violation['fix'] ?? null;
        if (!is_string($message) || !is_string($fix)) {
            throw new LintException(sprintf('%s: message and fix are required. Write what is wrong in message and what to change in fix.', $context));
        }
        $template = $this->template ?? new Template();
        $template->check($message, $context . '.message');
        $template->check($fix, $context . '.fix');

        return new Violation(is_bool($answer) || is_string($answer) ? $answer : null, is_numeric($minScore) ? (float) $minScore : null, is_numeric($maxScore) ? (float) $maxScore : null, $message, $fix);
    }

    /**
     * Requires the answer form the decision type uses: a boolean for a predicate, an option for a choice, a score bound for a score.
     *
     * @param mixed $answer the declared answer, or null
     * @param bool $unbounded whether neither min_score nor max_score is set
     * @throws LintException when the answer does not fit the decision type
     */
    public function checkAnswer(DecisionSpec $decision, $answer, bool $unbounded, string $context): void
    {
        if ($decision->type === Question::PREDICATE && !is_bool($answer)) {
            throw new LintException(sprintf('%s: answer must be true or false for a predicate decision. Set answer to the boolean answer that violates the rule.', $context));
        }
        if ($decision->type === Question::CHOICE && (!is_string($answer) || !array_key_exists($answer, $decision->choices))) {
            throw new LintException(sprintf('%s: answer must name one of the decision.choices (%s). Set answer to the option that violates the rule.', $context, implode(', ', array_keys($decision->choices))));
        }
        if ($decision->type === Question::SCORE && $unbounded) {
            throw new LintException(sprintf('%s: set min_score, max_score or both for a score decision. Scores run from 0 to %d.', $context, count($decision->levels) - 1));
        }
    }

    /**
     * Reads a value as a mapping with string keys, or an empty mapping.
     *
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function mapping($value): array
    {
        $mapping = [];
        foreach (is_array($value) ? $value : [] as $key => $entry) {
            $mapping[(string) $key] = $entry;
        }

        return $mapping;
    }

    /**
     * Reads a value as a list, or an empty list.
     *
     * @param mixed $value
     * @return list<mixed>
     */
    public function list($value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * Reads a value as a list of strings, or an empty list.
     *
     * @param mixed $value
     * @return list<string>
     */
    public function strings($value): array
    {
        $strings = [];
        foreach (is_array($value) ? $value : [] as $entry) {
            if (is_string($entry)) {
                $strings[] = $entry;
            }
        }

        return $strings;
    }
}
