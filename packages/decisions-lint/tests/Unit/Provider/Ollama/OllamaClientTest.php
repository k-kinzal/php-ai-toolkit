<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Ollama;

use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Ollama\OllamaClient;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Ollama\OllamaClient
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(OllamaClient::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(FakeTransport::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(ProviderSettings::class)]
final class OllamaClientTest extends TestCase
{
    public function testChatPostsTheModelMessagesFormatAndZeroTemperatureWithoutStreaming(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, '{"model":"llama3.1","message":{"role":"assistant","content":"{}"},"done":true}')]);
        $client = new OllamaClient(new JsonEndpoint($transport, ProviderSettings::defaults('ollama'), ''), 'llama3.1');
        $responses = $client->chat([['messages' => [['role' => 'user', 'content' => 'hi']], 'format' => ['type' => 'object']]]);
        self::assertSame('llama3.1', $responses[0]['model']);
        self::assertSame('http://localhost:11434/api/chat', $transport->requests[0]->url);
        self::assertSame('{"model":"llama3.1","messages":[{"role":"user","content":"hi"}],"stream":false,"format":{"type":"object"},"options":{"temperature":0}}', $transport->requests[0]->body);
        self::assertArrayNotHasKey('Authorization', $transport->requests[0]->headers);
    }
}
