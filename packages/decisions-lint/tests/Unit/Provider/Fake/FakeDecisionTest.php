<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeDecision;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\FakeDecision
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 */
#[CoversClass(FakeDecision::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(AnswerScript::class)]
final class FakeDecisionTest extends TestCase
{
    public function testDecideAnswersFromTheScriptAndRecordsTheInquiries(): void
    {
        $decision = new FakeDecision(new AnswerScript(static fn (Question $question): ?Answer => $question->name === 'no' ? Answer::predicate(0.0) : null), 'scripted');
        $first = new Inquiry(['a' => '1'], [new Question('yes', Question::PREDICATE, 'Y?'), new Question('no', Question::PREDICATE, 'N?')]);
        $second = new Inquiry(['b' => '2'], []);
        $verdicts = $decision->decide([$first, $second]);
        self::assertSame('scripted', $verdicts[0]->model);
        self::assertSame(1.0, $verdicts[0]->answer('yes')->probability);
        self::assertSame(0.0, $verdicts[0]->answer('no')->probability);
        self::assertSame([], $verdicts[1]->answers);
        self::assertSame([$first, $second], $decision->inquiries);
        self::assertSame('fake-1.0', (new FakeDecision())->decide([$second])[0]->model);
    }
}
