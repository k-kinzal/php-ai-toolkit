<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeJevTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\FakeJevTransport
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Payload
 */
#[CoversClass(FakeJevTransport::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(Payload::class)]
final class FakeJevTransportTest extends TestCase
{
    public function testSendAnswersASystemOneRequestInJevShape(): void
    {
        $transport = new FakeJevTransport(new AnswerScript(static fn (Question $question, array $subject): ?Answer => $question->type === Question::PREDICATE ? Answer::predicate($subject['name'] === 'bad' ? 0.1 : 0.9) : null), 'jev-9', new RequestBody());
        $body = '{"model":"jev-latest","state":{"name":"bad"},"questions":{"p":{"type":"noul","instructions":"P?"},"c":{"type":"choice","instructions":"C?","criteria":{"a":"A","b":"B"}},"s":{"type":"score","instructions":"S?","criteria":["low","high"]}}}';
        $responses = $transport->send([new HttpRequest('POST', 'https://api.typesafe.ai/v1/systemone', [], $body)]);
        self::assertCount(1, $transport->requests);
        self::assertSame(200, $responses[0]->status);
        self::assertSame('{"model":"jev-9","answers":{"p":{"type":"noul","noul":0.1},"c":{"type":"choice","choice":"a","confidence":1.0,"probabilities":{"a":1.0}},"s":{"type":"score","score":1.0,"confidence":1.0,"legend":["low","high"],"probabilities":{"1":1.0}}},"usage":{"input_tokens":0,"output_tokens":0}}', $responses[0]->body);
    }

    public function testRespondRejectsARequestWithoutAModel(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev fake request: field "model" must be a string, but it is missing');
        (new FakeJevTransport())->respond(new HttpRequest('POST', 'https://api.typesafe.ai/v1/systemone', [], '{"state":{},"questions":{}}'));
    }

    public function testQuestionRebuildsEachType(): void
    {
        $transport = new FakeJevTransport();
        $choice = $transport->question('c', new Payload(['type' => 'choice', 'instructions' => 'C?', 'criteria' => ['a' => 'A']], 'ctx'));
        self::assertSame(Question::CHOICE, $choice->type);
        self::assertSame(['a' => 'A'], $choice->choices);
        $score = $transport->question('s', new Payload(['type' => 'score', 'instructions' => 'S?', 'criteria' => ['low', 'high']], 'ctx'));
        self::assertSame(['low', 'high'], $score->levels);
        self::assertSame(Question::PREDICATE, $transport->question('p', new Payload(['type' => 'noul', 'instructions' => 'P?'], 'ctx'))->type);
    }

    public function testEncodeWritesEachAnswerType(): void
    {
        $transport = new FakeJevTransport();
        self::assertSame(['type' => 'noul', 'noul' => 0.3], $transport->encode(Answer::predicate(0.3), new Question('p', Question::PREDICATE, 'P?')));
        self::assertSame(['type' => 'choice', 'choice' => 'a', 'confidence' => 0.8, 'probabilities' => ['a' => 0.8]], $transport->encode(Answer::choice('a', 0.8, ['a' => 0.8]), new Question('c', Question::CHOICE, 'C?', ['a' => 'A'])));
        self::assertSame(['type' => 'score', 'score' => 1.0, 'confidence' => 0.5, 'legend' => ['x', 'y'], 'probabilities' => ['1' => 0.5]], $transport->encode(Answer::score(1.0, 0.5, ['1' => 0.5]), new Question('s', Question::SCORE, 'S?', [], ['x', 'y'])));
    }
}
