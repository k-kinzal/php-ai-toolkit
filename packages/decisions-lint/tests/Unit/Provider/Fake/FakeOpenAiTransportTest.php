<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeOpenAiTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\FakeOpenAiTransport
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Payload
 */
#[CoversClass(FakeOpenAiTransport::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(Payload::class)]
final class FakeOpenAiTransportTest extends TestCase
{
    public function testSendAnswersADecisionsRequestInOpenAiShape(): void
    {
        $transport = new FakeOpenAiTransport(new AnswerScript(static fn (Question $question, array $subject): ?Answer => $question->type === Question::PREDICATE ? Answer::predicate(str_contains($subject['input'], 'bad') ? 0.2 : 0.8) : null), 'luna-test', new RequestBody());
        $body = '{"model":"gpt-6-luna","input":"## name\n\nbad\n","questions":[{"type":"predicate","name":"p","instructions":"P?"},{"type":"choice","name":"c","instructions":"C?","choices":[{"value":"a","description":"A"}]},{"type":"score","name":"s","instructions":"S?","levels":[{"label":"0","description":"low"},{"label":"1","description":"high"}]}]}';
        $responses = $transport->send([new HttpRequest('POST', 'https://api.openai.com/v1/decisions', [], $body)]);
        self::assertCount(1, $transport->requests);
        self::assertSame('{"model":"luna-test","answers":[{"type":"predicate","name":"p","probability":0.2},{"type":"choice","name":"c","choice":"a","confidence":1.0,"probabilities":[{"value":"a","label":"a","probability":1.0}]},{"type":"score","name":"s","score":1.0,"confidence":1.0,"probabilities":[{"value":"1","label":"1","probability":1.0}]}]}', $responses[0]->body);
    }

    public function testRespondRejectsARequestWithoutInput(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('OpenAI fake request: field "input" must be a string, but it is missing');
        (new FakeOpenAiTransport())->respond(new HttpRequest('POST', 'https://api.openai.com/v1/decisions', [], '{"model":"gpt-6-luna","questions":[]}'));
    }

    public function testQuestionRebuildsEachType(): void
    {
        $transport = new FakeOpenAiTransport();
        $choice = $transport->question(new Payload(['name' => 'c', 'type' => 'choice', 'instructions' => 'C?', 'choices' => [['value' => 'a', 'description' => 'A']]], 'ctx'));
        self::assertSame(['a' => 'A'], $choice->choices);
        $score = $transport->question(new Payload(['name' => 's', 'type' => 'score', 'instructions' => 'S?', 'levels' => [['label' => '0', 'description' => 'low']]], 'ctx'));
        self::assertSame(['low'], $score->levels);
        self::assertSame(Question::PREDICATE, $transport->question(new Payload(['name' => 'p', 'type' => 'predicate', 'instructions' => 'P?'], 'ctx'))->type);
    }

    public function testEncodeWritesEachAnswerType(): void
    {
        $transport = new FakeOpenAiTransport();
        self::assertSame(['type' => 'predicate', 'name' => 'p', 'probability' => 0.3], $transport->encode(Answer::predicate(0.3), new Question('p', Question::PREDICATE, 'P?')));
        self::assertSame(['type' => 'choice', 'name' => 'c', 'choice' => 'a', 'confidence' => 0.8, 'probabilities' => [['value' => 'a', 'label' => 'a', 'probability' => 0.8]]], $transport->encode(Answer::choice('a', 0.8, ['a' => 0.8]), new Question('c', Question::CHOICE, 'C?', ['a' => 'A'])));
        self::assertSame(['type' => 'score', 'name' => 's', 'score' => 1.0, 'confidence' => 0.5, 'probabilities' => [['value' => '1', 'label' => '1', 'probability' => 0.5]]], $transport->encode(Answer::score(1.0, 0.5, ['1' => 0.5]), new Question('s', Question::SCORE, 'S?', [], ['x', 'y'])));
    }
}
