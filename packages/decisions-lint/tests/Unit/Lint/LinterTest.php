<?php

declare(strict_types=1);

namespace Tests\Unit\Lint;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Linter;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Lint\Report;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeDecision;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use DecisionsLint\Rule\ViolationMatcher;
use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Lint\Linter
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeDecision
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 * @uses \DecisionsLint\Rule\ViolationMatcher
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(Linter::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Severity::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Report::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeDecision::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationMatcher::class)]
#[UsesClass(Target::class)]
final class LinterTest extends TestCase
{
    public function testLintTurnsViolatingAnswersIntoSortedFindings(): void
    {
        $rule = new Rule('cohesion', '', 'warning', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Fits?', [], [], 0.7), [new Violation(false, null, null, '{name} misplaced.', 'Move {name}.')]);
        $first = new Target('method', 'zed', 'B::zed', '', 'B', 'src/B.php', 3, 5, 'function zed()', 'public', [], 'D', 'C', 'F');
        $second = new Target('method', 'foo', 'A::foo', '', 'A', 'src/A.php', 9, 5, 'function foo()', 'public', [], 'D', 'C', 'F');
        $third = new Target('method', 'bar', 'A::bar', '', 'A', 'src/A.php', 2, 5, 'function bar()', 'public', [], 'D', 'C', 'F');
        $plan = new Plan(['src/A.php', 'src/B.php'], [
            new Inquiry(['name' => 'B::zed'], [new Question('cohesion', 'predicate', 'Fits?')]),
            new Inquiry(['name' => 'A::foo'], [new Question('cohesion', 'predicate', 'Fits?')]),
            new Inquiry(['name' => 'A::bar'], [new Question('cohesion', 'predicate', 'Fits?')]),
        ], [new Assignment(0, $rule, $first), new Assignment(1, $rule, $second), new Assignment(2, $rule, $third)]);
        $decision = new FakeDecision(new AnswerScript(static fn (Question $question, array $subject): Answer => Answer::predicate($subject['name'] === 'A::foo' ? 0.95 : 0.1)), 'fake-jev');
        $report = (new Linter(new ViolationMatcher()))->lint($plan, $decision, 'warning');
        self::assertSame(['src/A.php:2 bar misplaced.', 'src/B.php:3 zed misplaced.'], array_map(static fn (Finding $finding): string => $finding->path . ':' . $finding->line . ' ' . $finding->message, $report->findings));
        self::assertSame(2, $report->files);
        self::assertSame(3, $report->targets);
        self::assertSame(3, $report->requests);
        self::assertSame(['fake-jev'], $report->models);
        self::assertTrue($report->failed());
        self::assertCount(3, $decision->inquiries);
    }

    public function testLintAsksNothingForAnEmptyPlan(): void
    {
        $decision = new FakeDecision();
        $report = (new Linter())->lint(new Plan([], [], []), $decision, 'error');
        self::assertSame([], $report->findings);
        self::assertSame(0, $report->requests);
        self::assertFalse($report->failed());
        self::assertSame([], $decision->inquiries);
    }

    public function testSortOrdersByPathLineColumnAndRule(): void
    {
        $findings = [
            new Finding('b.php', 1, 1, 'a', 'error', 's', 'm', 'f', 1.0),
            new Finding('a.php', 2, 1, 'b', 'error', 's', 'm', 'f', 1.0),
            new Finding('a.php', 2, 1, 'a', 'error', 's', 'm', 'f', 1.0),
            new Finding('a.php', 1, 9, 'a', 'error', 's', 'm', 'f', 1.0),
        ];
        self::assertSame(['a.php:1:9:a', 'a.php:2:1:a', 'a.php:2:1:b', 'b.php:1:1:a'], array_map(static fn (Finding $finding): string => $finding->path . ':' . $finding->line . ':' . $finding->column . ':' . $finding->rule, (new Linter())->sort($findings)));
    }
}
