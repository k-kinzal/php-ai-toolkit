<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\ChatRequestReader;
use DecisionsLint\Provider\Fake\FakeOllamaTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\FakeOllamaTransport
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\ChatRequestReader
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Payload
 */
#[CoversClass(FakeOllamaTransport::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(ChatRequestReader::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(Payload::class)]
final class FakeOllamaTransportTest extends TestCase
{
    public function testSendAnswersAChatRequestInOllamaShape(): void
    {
        $transport = new FakeOllamaTransport(new AnswerScript(static fn (): Answer => Answer::predicate(0.25)), 'llama3.1:8b', new RequestBody(), new ChatRequestReader());
        $body = '{"model":"llama3.1","messages":[{"role":"user","content":"source"}],"stream":false,"format":{"type":"object","properties":{"p":{"properties":{"probability":{"type":"number"}}}}}}';
        $responses = $transport->send([new HttpRequest('POST', 'http://localhost:11434/api/chat', [], $body)]);
        self::assertCount(1, $transport->requests);
        self::assertSame('{"model":"llama3.1:8b","message":{"role":"assistant","content":"{\"p\":{\"probability\":0.25}}"},"done":true}', $responses[0]->body);
    }

    public function testRespondRejectsARequestWithoutAFormat(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Ollama fake request: field "format" must be a mapping or list, but it is missing');
        (new FakeOllamaTransport())->respond(new HttpRequest('POST', 'http://localhost:11434/api/chat', [], '{"model":"llama3.1","messages":[]}'));
    }
}
