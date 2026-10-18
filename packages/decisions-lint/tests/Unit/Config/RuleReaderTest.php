<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\RuleReader;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\RuleReader
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 */
#[CoversClass(RuleReader::class)]
#[UsesClass(LintException::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
final class RuleReaderTest extends TestCase
{
    public function testReadBuildsACompleteRuleWithDefaults(): void
    {
        $rule = (new RuleReader(new Template()))->read('a.b', [
            'source' => ['rules/a.yaml'],
            'target' => ['kind' => 'method', 'visibility' => ['public'], 'exclude_names' => ['__*'], 'static' => false],
            'decision' => ['type' => 'choice', 'instructions' => 'Classify {name}.', 'choices' => ['ok' => 'fine', 'leak' => 'bad'], 'threshold' => 0.6],
            'violations' => [['answer' => 'leak', 'message' => 'Leak in {name}.', 'fix' => 'Move it.']],
        ]);
        self::assertSame('a.b', $rule->id);
        self::assertSame('', $rule->description);
        self::assertSame('error', $rule->severity);
        self::assertTrue($rule->enabled);
        self::assertSame([], $rule->paths);
        self::assertSame('class', $rule->context);
        self::assertSame('method', $rule->target->kind);
        self::assertFalse($rule->target->static);
        self::assertSame(['ok' => 'fine', 'leak' => 'bad'], $rule->decision->choices);
        self::assertSame(0.6, $rule->decision->threshold);
        self::assertSame('leak', $rule->violations[0]->answer);
    }

    public function testReadRequiresTheRuleParts(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Rule "a.b" (decisions.yaml) has no target. Add it, or import the rule file that defines the rule before overriding it in decisions.yaml.');
        (new RuleReader())->read('a.b', ['source' => ['decisions.yaml'], 'severity' => 'error']);
    }

    public function testTargetDefaultsToTheWholeFile(): void
    {
        $selector = (new RuleReader())->target([]);
        self::assertSame('file', $selector->kind);
        self::assertSame([], $selector->visibility);
        self::assertNull($selector->static);
    }

    /**
     * @dataProvider providerDecisionProblems
     * @param array<string, mixed> $decision
     */
    #[DataProvider('providerDecisionProblems')]
    public function testDecisionRejectsIncompleteQuestions(array $decision, string $expected): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage($expected);
        (new RuleReader())->decision($decision, 'Rule "x" (a.yaml)');
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function providerDecisionProblems(): iterable
    {
        yield 'no type' => [['instructions' => 'Q?'], 'Rule "x" (a.yaml): decision.type is missing. Set it to predicate, choice or score.'];
        yield 'no instructions' => [['type' => 'predicate'], 'Rule "x" (a.yaml): decision.instructions is missing. Write the question the model answers about each target.'];
        yield 'unknown placeholder' => [['type' => 'predicate', 'instructions' => 'Is {method} fine?'], 'Rule "x" (a.yaml): decision.instructions uses the unknown placeholder {method}.'];
        yield 'choice without options' => [['type' => 'choice', 'instructions' => 'Pick.', 'choices' => ['only' => 'one']], 'Rule "x" (a.yaml): decision.choices must list at least 2 options when decision.type is choice.'];
        yield 'score without levels' => [['type' => 'score', 'instructions' => 'Rate.'], 'Rule "x" (a.yaml): decision.levels must list at least 2 levels when decision.type is score.'];
    }

    public function testDecisionFillsTheThresholdDefault(): void
    {
        $decision = (new RuleReader())->decision(['type' => 'score', 'instructions' => 'Rate.', 'levels' => ['a', 'b'], 'threshold' => 1], 'ctx');
        self::assertSame(1.0, $decision->threshold);
        self::assertSame(0.5, (new RuleReader())->decision(['type' => 'predicate', 'instructions' => 'Q?'], 'ctx')->threshold);
    }

    /**
     * @dataProvider providerViolationProblems
     * @param array<string, mixed> $violation
     */
    #[DataProvider('providerViolationProblems')]
    public function testViolationRequiresTheAnswerFormOfTheDecisionType(DecisionSpec $decision, array $violation, string $expected): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage($expected);
        (new RuleReader())->violation($violation, $decision, 'Rule "x" (a.yaml).violations[0]');
    }

    /**
     * @return iterable<string, array{DecisionSpec, array<string, mixed>, string}>
     */
    public static function providerViolationProblems(): iterable
    {
        yield 'predicate without boolean' => [new DecisionSpec('predicate', 'Q?'), ['answer' => 'no', 'message' => 'm', 'fix' => 'f'], 'answer must be true or false for a predicate decision.'];
        yield 'choice with unknown option' => [new DecisionSpec('choice', 'Q?', ['a' => 'A', 'b' => 'B']), ['answer' => 'c', 'message' => 'm', 'fix' => 'f'], 'answer must name one of the decision.choices (a, b).'];
        yield 'score without bounds' => [new DecisionSpec('score', 'Q?', [], ['x', 'y', 'z']), ['message' => 'm', 'fix' => 'f'], 'set min_score, max_score or both for a score decision. Scores run from 0 to 2.'];
        yield 'missing fix' => [new DecisionSpec('predicate', 'Q?'), ['answer' => false, 'message' => 'm'], 'message and fix are required.'];
        yield 'bad placeholder in fix' => [new DecisionSpec('predicate', 'Q?'), ['answer' => false, 'message' => 'm', 'fix' => '{nope}'], 'Rule "x" (a.yaml).violations[0].fix uses the unknown placeholder {nope}.'];
    }

    public function testCheckAnswerAcceptsTheFormOfEachType(): void
    {
        $this->expectNotToPerformAssertions();
        $reader = new RuleReader();
        $reader->checkAnswer(new DecisionSpec('predicate', 'Q?'), true, true, 'ctx');
        $reader->checkAnswer(new DecisionSpec('choice', 'Q?', ['a' => 'A', 'b' => 'B']), 'b', true, 'ctx');
        $reader->checkAnswer(new DecisionSpec('score', 'Q?', [], ['x', 'y']), null, false, 'ctx');
    }

    public function testViolationReadsScoreBounds(): void
    {
        $violation = (new RuleReader())->violation(['min_score' => 1, 'max_score' => 1.5, 'message' => 'm', 'fix' => 'f'], new DecisionSpec('score', 'Q?', [], ['x', 'y']), 'ctx');
        self::assertNull($violation->answer);
        self::assertSame(1.0, $violation->minScore);
        self::assertSame(1.5, $violation->maxScore);
    }

    public function testMappingReadsMappingsAndIgnoresOtherValues(): void
    {
        self::assertSame(['a' => 1], (new RuleReader())->mapping(['a' => 1]));
        self::assertSame([], (new RuleReader())->mapping(1));
    }

    public function testListReadsListsAndIgnoresOtherValues(): void
    {
        self::assertSame([1, 2], (new RuleReader())->list(['x' => 1, 'y' => 2]));
        self::assertSame([], (new RuleReader())->list('x'));
    }

    public function testStringsKeepsOnlyStrings(): void
    {
        self::assertSame(['a'], (new RuleReader())->strings(['a', 2]));
    }
}
