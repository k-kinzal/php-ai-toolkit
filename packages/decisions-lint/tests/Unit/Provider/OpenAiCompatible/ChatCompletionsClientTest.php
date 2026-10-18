<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\OpenAiCompatible;

use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(ChatCompletionsClient::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(FakeTransport::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(ProviderSettings::class)]
final class ChatCompletionsClientTest extends TestCase
{
    public function testCompletePostsAStrictJsonSchemaResponseFormat(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, '{"model":"gpt-4","choices":[{"message":{"role":"assistant","content":"{}"}}]}')]);
        $client = new ChatCompletionsClient(new JsonEndpoint($transport, ProviderSettings::defaults('localai'), 'k'), 'gpt-4');
        $responses = $client->complete([['messages' => [['role' => 'user', 'content' => 'hi']], 'schema' => ['type' => 'object']]]);
        self::assertSame('gpt-4', $responses[0]['model']);
        self::assertSame('http://localhost:8080/v1/chat/completions', $transport->requests[0]->url);
        self::assertSame('Bearer k', $transport->requests[0]->headers['Authorization']);
        self::assertSame('{"model":"gpt-4","messages":[{"role":"user","content":"hi"}],"temperature":0,"response_format":{"type":"json_schema","json_schema":{"name":"decisions","strict":true,"schema":{"type":"object"}}}}', $transport->requests[0]->body);
    }
}
