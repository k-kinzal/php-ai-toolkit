<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use DecisionsLint\Rule\ViolationMatcher;
use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\ViolationMatcher
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(ViolationMatcher::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Finding::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
#[UsesClass(Target::class)]
final class ViolationMatcherTest extends TestCase
{
    public function testMatchReturnsTheFirstViolationTheAnswerSatisfies(): void
    {
        $rule = new Rule('ddd.leak', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('choice', 'Classify.', ['ok' => 'fine', 'leak' => 'bad', 'infra' => 'worse'], [], 0.6), [
            new Violation('leak', null, null, 'Leak in {class}::{name} ({choice}, {confidence}).', 'Move it out of {name}().'),
            new Violation('infra', null, null, 'Infra.', 'Hide it.'),
        ]);
        $target = new Target('method', 'handle', 'App\UseCase::handle', 'App', 'UseCase', 'src/UseCase.php', 20, 5, 'public function handle()', 'public', ['public'], 'D', 'C', 'F');
        $finding = (new ViolationMatcher(new Template()))->match($rule, $target, Answer::choice('leak', 0.9, ['leak' => 0.9]));
        self::assertNotNull($finding);
        self::assertSame('Leak in UseCase::handle (leak, 0.90).', $finding->message);
        self::assertSame('Move it out of handle().', $finding->fix);
        self::assertSame('error', $finding->severity);
        self::assertSame(20, $finding->line);
        self::assertSame(0.9, $finding->confidence);
        self::assertNull((new ViolationMatcher())->match($rule, $target, Answer::choice('ok', 0.9, [])));
        self::assertNull((new ViolationMatcher())->match($rule, $target, Answer::choice('leak', 0.3, [])));
        self::assertNull((new ViolationMatcher())->match($rule, $target, Answer::refusal()));
    }

    /**
     * @dataProvider providerViolates
     */
    #[DataProvider('providerViolates')]
    public function testViolatesComparesTheAnswerWithTheThreshold(DecisionSpec $decision, Violation $violation, Answer $answer, bool $expected): void
    {
        self::assertSame($expected, (new ViolationMatcher())->violates($decision, $violation, $answer));
    }

    /**
     * @return iterable<string, array{DecisionSpec, Violation, Answer, bool}>
     */
    public static function providerViolates(): iterable
    {
        $predicate = new DecisionSpec('predicate', 'Q?', [], [], 0.7);
        yield 'predicate false confidently' => [$predicate, new Violation(false, null, null, 'm', 'f'), Answer::predicate(0.2), true];
        yield 'predicate false weakly' => [$predicate, new Violation(false, null, null, 'm', 'f'), Answer::predicate(0.4), false];
        yield 'predicate true confidently' => [$predicate, new Violation(true, null, null, 'm', 'f'), Answer::predicate(0.8), true];
        $score = new DecisionSpec('score', 'Rate.', [], ['a', 'b', 'c'], 0.5);
        yield 'score within bounds' => [$score, new Violation(null, 0.5, 1.5, 'm', 'f'), Answer::score(1.0, 0.6, []), true];
        yield 'score above max' => [$score, new Violation(null, null, 0.5, 'm', 'f'), Answer::score(1.0, 0.6, []), false];
        yield 'score below min' => [$score, new Violation(null, 1.5, null, 'm', 'f'), Answer::score(1.0, 0.6, []), false];
        yield 'score with low confidence' => [$score, new Violation(null, 0.0, null, 'm', 'f'), Answer::score(1.0, 0.2, []), false];
    }

    public function testFindingRendersTheAnswerValuesIntoTheTemplates(): void
    {
        $rule = new Rule('naming.vague', '', 'warning', true, [], [], new TargetSelector('method'), 'declaration', new DecisionSpec('score', 'Rate.', [], ['a', 'b', 'c'], 0.5), []);
        $target = new Target('method', 'doIt', 'App\A::doIt', 'App', 'A', 'src/A.php', 8, 5, 'public function doIt()', 'public', ['public'], 'D', 'C', 'F');
        $finding = (new ViolationMatcher())->finding($rule, $target, new Violation(null, 0.5, 1.5, '{name} scored {score} ({answer}) for {rule}.', 'Rename; threshold {threshold}.'), Answer::score(1.25, 0.55, []));
        self::assertSame('doIt scored 1.25 (1.25) for naming.vague.', $finding->message);
        self::assertSame('Rename; threshold 0.5.', $finding->fix);
        self::assertSame('warning', $finding->severity);
        self::assertSame('App\A::doIt', $finding->symbol);
        self::assertSame(0.55, $finding->confidence);
    }

    public function testAnswerTextNamesTheAnswerForEachType(): void
    {
        $matcher = new ViolationMatcher();
        self::assertSame('yes', $matcher->answerText(Answer::predicate(0.6)));
        self::assertSame('no', $matcher->answerText(Answer::predicate(0.4)));
        self::assertSame('leak', $matcher->answerText(Answer::choice('leak', 1.0, [])));
        self::assertSame('1.50', $matcher->answerText(Answer::score(1.5, 1.0, [])));
    }
}
