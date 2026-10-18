<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Provider\Fake\AnswerScript;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 */
#[CoversClass(AnswerScript::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
final class AnswerScriptTest extends TestCase
{
    public function testAnswerAsksTheClosureAndFallsBackToTheAgreeableAnswer(): void
    {
        $script = new AnswerScript(static fn (Question $question, array $subject): ?Answer => $subject['name'] === 'bad' ? Answer::predicate(0.0) : null);
        $question = new Question('p', Question::PREDICATE, 'P?');
        self::assertSame(0.0, $script->answer($question, ['name' => 'bad'])->probability);
        self::assertSame(1.0, $script->answer($question, ['name' => 'good'])->probability);
        self::assertSame(1.0, (new AnswerScript())->answer($question, [])->probability);
    }

    public function testAgreeablePicksYesTheFirstOptionOrTheTopLevel(): void
    {
        $script = new AnswerScript();
        self::assertSame(1.0, $script->agreeable(new Question('p', Question::PREDICATE, 'P?'))->probability);
        $choice = $script->agreeable(new Question('c', Question::CHOICE, 'C?', ['ok' => 'fine', 'bad' => 'worse']));
        self::assertSame('ok', $choice->choice);
        self::assertSame(['ok' => 1.0], $choice->probabilities);
        self::assertSame('', $script->agreeable(new Question('c', Question::CHOICE, 'C?'))->choice);
        $score = $script->agreeable(new Question('s', Question::SCORE, 'S?', [], ['low', 'mid', 'high']));
        self::assertSame(2.0, $score->score);
        self::assertSame(['2' => 1.0], $score->probabilities);
    }
}
