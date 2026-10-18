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
use DecisionsLint\Provider\Fake\FakeChatCompletionsTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\FakeChatCompletionsTransport
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
#[CoversClass(FakeChatCompletionsTransport::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(ChatRequestReader::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(Payload::class)]
final class FakeChatCompletionsTransportTest extends TestCase
{
    public function testSendAnswersACompletionRequestInOpenAiChatShape(): void
    {
        $transport = new FakeChatCompletionsTransport(new AnswerScript(static fn (): Answer => Answer::choice('b', 0.9, ['b' => 0.9])), 'local', new RequestBody(), new ChatRequestReader());
        $body = '{"model":"gpt-4","messages":[{"role":"user","content":"source"}],"temperature":0,"response_format":{"type":"json_schema","json_schema":{"name":"decisions","strict":true,"schema":{"type":"object","properties":{"c":{"properties":{"choice":{"type":"string","enum":["a","b"]},"confidence":{"type":"number"}}}}}}}}';
        $responses = $transport->send([new HttpRequest('POST', 'http://localhost:8080/v1/chat/completions', [], $body)]);
        self::assertCount(1, $transport->requests);
        self::assertSame('{"model":"local","choices":[{"index":0,"finish_reason":"stop","message":{"role":"assistant","content":"{\"c\":{\"choice\":\"b\",\"confidence\":0.9}}"}}]}', $responses[0]->body);
    }

    public function testRespondRejectsARequestWithoutAResponseFormat(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Chat completions fake request: field "response_format" must be a mapping or list, but it is missing');
        (new FakeChatCompletionsTransport())->respond(new HttpRequest('POST', 'http://localhost:8080/v1/chat/completions', [], '{"model":"gpt-4","messages":[]}'));
    }
}
