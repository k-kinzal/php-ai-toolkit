<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Jev;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeJevTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Jev\JevDecision
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeJevTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(JevDecision::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeJevTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JevClient::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class JevDecisionTest extends TestCase
{
    public function testDecideSendsOneRequestPerInquiryAndReadsEveryAnswer(): void
    {
        $script = new AnswerScript(static fn (Question $question, array $subject): Answer => match ($question->type) {
            Question::CHOICE => Answer::choice('b', 0.8, ['a' => 0.2, 'b' => 0.8]),
            Question::SCORE => Answer::score(1.5, 0.6, ['0' => 0.1, '1' => 0.3, '2' => 0.6]),
            default => Answer::predicate($subject['file'] === 'a.php' ? 0.9 : 0.1),
        });
        $transport = new FakeJevTransport($script, 'jev-1.13.0');
        $decision = new JevDecision(new JevClient(new JsonEndpoint($transport, ProviderSettings::defaults('jev'), 'k'), 'jev-latest'));
        $questions = [
            new Question('p', Question::PREDICATE, 'P?'),
            new Question('c', Question::CHOICE, 'C?', ['a' => 'A', 'b' => 'B']),
            new Question('s', Question::SCORE, 'S?', [], ['low', 'mid', 'high']),
        ];
        $verdicts = $decision->decide([new Inquiry(['file' => 'a.php', 'declaration' => 'code'], $questions), new Inquiry(['file' => 'b.php'], $questions)]);
        self::assertCount(2, $verdicts);
        self::assertSame('jev-1.13.0', $verdicts[0]->model);
        self::assertSame(0.9, $verdicts[0]->answer('p')->probability);
        self::assertSame(0.1, $verdicts[1]->answer('p')->probability);
        self::assertSame('b', $verdicts[0]->answer('c')->choice);
        self::assertSame(['a' => 0.2, 'b' => 0.8], $verdicts[0]->answer('c')->probabilities);
        self::assertSame(1.5, $verdicts[0]->answer('s')->score);
        self::assertStringContainsString('"state":{"file":"a.php","declaration":"code"}', $transport->requests[0]->body);
    }

    public function testDecideRefusesAQuestionTheProviderDidNotAnswer(): void
    {
        $decision = new JevDecision(new JevClient(new JsonEndpoint(new FakeJevTransport(), ProviderSettings::defaults('jev'), 'k'), 'jev-latest'));
        $verdict = $decision->verdict(['model' => 'jev-1.13.0', 'answers' => []], new Inquiry([], [new Question('missing', Question::PREDICATE, 'M?')]));
        self::assertSame(Answer::REFUSAL, $verdict->answer('missing')->type);
    }

    public function testRequestKeysQuestionsByNameAroundTheSubjectState(): void
    {
        $decision = new JevDecision(new JevClient(new JsonEndpoint(new FakeJevTransport(), ProviderSettings::defaults('jev'), 'k'), 'jev-latest'));
        $request = $decision->request(new Inquiry(['file' => 'a.php'], [new Question('x', Question::PREDICATE, 'X?')]));
        self::assertSame(['file' => 'a.php'], $request['state']);
        self::assertSame(['x' => ['type' => 'noul', 'instructions' => 'X?']], $request['questions']);
    }

    public function testQuestionMapsEachTypeToItsJevForm(): void
    {
        $decision = new JevDecision(new JevClient(new JsonEndpoint(new FakeJevTransport(), ProviderSettings::defaults('jev'), 'k'), 'jev-latest'));
        self::assertSame(['type' => 'noul', 'instructions' => 'P?'], $decision->question(new Question('p', Question::PREDICATE, 'P?')));
        self::assertSame(['type' => 'choice', 'instructions' => 'C?', 'criteria' => ['a' => 'A']], $decision->question(new Question('c', Question::CHOICE, 'C?', ['a' => 'A'])));
        self::assertSame(['type' => 'score', 'instructions' => 'S?', 'criteria' => ['low', 'high']], $decision->question(new Question('s', Question::SCORE, 'S?', [], ['low', 'high'])));
    }

    public function testVerdictRejectsAResponseWithoutAnswers(): void
    {
        $decision = new JevDecision(new JevClient(new JsonEndpoint(new FakeJevTransport(), ProviderSettings::defaults('jev'), 'k'), 'jev-latest'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev response: field "answers" must be a mapping or list, but it is missing');
        $decision->verdict(['model' => 'jev-1.13.0'], new Inquiry([], []));
    }

    public function testAnswerReadsTheFieldOfEachType(): void
    {
        $decision = new JevDecision(new JevClient(new JsonEndpoint(new FakeJevTransport(), ProviderSettings::defaults('jev'), 'k'), 'jev-latest'));
        self::assertSame(0.25, $decision->answer(new Payload(['noul' => 0.25], 'ctx'), new Question('p', Question::PREDICATE, 'P?'))->probability);
        self::assertSame('a', $decision->answer(new Payload(['choice' => 'a', 'confidence' => 1, 'probabilities' => ['a' => 1]], 'ctx'), new Question('c', Question::CHOICE, 'C?'))->choice);
        self::assertSame(0.5, $decision->answer(new Payload(['score' => 0.5, 'confidence' => 0.4, 'probabilities' => ['0' => 0.5, '1' => 0.5]], 'ctx'), new Question('s', Question::SCORE, 'S?'))->score);
    }
}
