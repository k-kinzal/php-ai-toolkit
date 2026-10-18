<?php

declare(strict_types=1);

namespace DecisionsLint\Lint;

use DecisionsLint\Decision\Decision;
use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Rule\ViolationMatcher;

/**
 * Sends a plan to the decision model and turns its answers into findings.
 */
final class Linter
{
    /**
     * Creates a linter, optionally with a shared matcher.
     */
    public function __construct(private ?ViolationMatcher $matcher = null)
    {
    }

    /**
     * Runs the plan against a decision and reports what violates the rules.
     *
     * @throws LintException when the provider rejects a request or cannot be reached
     */
    public function lint(Plan $plan, Decision $decision, string $failOn): Report
    {
        $verdicts = $plan->inquiries === [] ? [] : $decision->decide($plan->inquiries);
        $matcher = $this->matcher ?? new ViolationMatcher();
        $findings = [];
        $models = [];
        foreach ($plan->assignments as $assignment) {
            $verdict = $verdicts[$assignment->inquiry];
            $models[$verdict->model] = true;
            $finding = $matcher->match($assignment->rule, $assignment->target, $verdict->answer($assignment->rule->id));
            if ($finding !== null) {
                $findings[] = $finding;
            }
        }

        return new Report($this->sort($findings), count($plan->files), $plan->targets(), count($verdicts), array_keys($models), $failOn);
    }

    /**
     * Orders findings by file, line, column and rule.
     *
     * @param list<Finding> $findings
     * @return list<Finding>
     */
    public function sort(array $findings): array
    {
        usort($findings, static fn (Finding $a, Finding $b): int => [$a->path, $a->line, $a->column, $a->rule] <=> [$b->path, $b->line, $b->column, $b->rule]);

        return $findings;
    }
}
