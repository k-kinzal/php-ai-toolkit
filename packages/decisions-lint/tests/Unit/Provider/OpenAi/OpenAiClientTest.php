<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\OpenAi;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeOpenAiTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\OpenAi\OpenAiClient
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeOpenAiTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(OpenAiClient::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeOpenAiTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class OpenAiClientTest extends TestCase
{
    public function testDecisionsPostsTheModelInputAndQuestions(): void
    {
        $transport = new FakeOpenAiTransport(new AnswerScript(static fn (): Answer => Answer::predicate(0.4)), 'gpt-6-luna');
        $client = new OpenAiClient(new JsonEndpoint($transport, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna');
        $responses = $client->decisions([['input' => 'text', 'questions' => [['type' => 'predicate', 'name' => 'q', 'instructions' => 'Q?']]]]);
        self::assertSame([['type' => 'predicate', 'name' => 'q', 'probability' => 0.4]], $responses[0]['answers']);
        self::assertSame('https://api.openai.com/v1/decisions', $transport->requests[0]->url);
        self::assertSame('{"model":"gpt-6-luna","input":"text","questions":[{"type":"predicate","name":"q","instructions":"Q?"}]}', $transport->requests[0]->body);
    }
}
