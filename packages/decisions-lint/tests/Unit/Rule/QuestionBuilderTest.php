<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\QuestionBuilder;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\QuestionBuilder
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(QuestionBuilder::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
#[UsesClass(Target::class)]
final class QuestionBuilderTest extends TestCase
{
    public function testQuestionFillsTheTargetIntoTheInstructions(): void
    {
        $rule = new Rule('cohesion.method', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('choice', 'Does {class}::{name} fit rule {rule} at {threshold}?', ['a' => 'A', 'b' => 'B']), []);
        $target = new Target('method', 'foo', 'App\Analyzer::foo', 'App', 'Analyzer', 'src/Analyzer.php', 3, 5, 'public function foo()', 'public', ['public'], 'D', 'C', 'F');
        $question = (new QuestionBuilder(new Template()))->question($rule, $target);
        self::assertSame('cohesion.method', $question->name);
        self::assertSame('choice', $question->type);
        self::assertSame('Does Analyzer::foo fit rule cohesion.method at 0.5?', $question->instructions);
        self::assertSame(['a' => 'A', 'b' => 'B'], $question->choices);
    }

    public function testSubjectAddsTheContextSourceUnderItsOwnName(): void
    {
        $builder = new QuestionBuilder();
        $target = new Target('method', 'foo', 'App\Analyzer::foo', 'App', 'Analyzer', 'src/Analyzer.php', 3, 5, 'public function foo()', 'public', ['public'], 'DECL', 'CLASS', 'FILE');
        self::assertSame(['file' => 'src/Analyzer.php', 'kind' => 'method', 'name' => 'App\Analyzer::foo', 'signature' => 'public function foo()', 'declaration' => 'DECL'], $builder->subject($target, 'declaration'));
        self::assertSame('CLASS', $builder->subject($target, 'class')['class_source']);
        self::assertSame('FILE', $builder->subject($target, 'file')['file_source']);
    }

    public function testInquiryAsksEveryRuleAboutTheSameSubject(): void
    {
        $first = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'A?'), [new Violation(false, null, null, 'm', 'f')]);
        $second = new Rule('b', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('score', 'B?', [], ['x', 'y']), []);
        $target = new Target('method', 'foo', 'A::foo', '', 'A', 'src/A.php', 1, 1, 'function foo()', 'public', [], 'D', 'C', 'F');
        $inquiry = (new QuestionBuilder())->inquiry($target, 'class', [$first, $second]);
        self::assertSame(['a', 'b'], array_map(static fn (Question $question): string => $question->name, $inquiry->questions));
        self::assertSame('C', $inquiry->subject['class_source']);
        self::assertSame(['x', 'y'], $inquiry->questions[1]->levels);
    }
}
