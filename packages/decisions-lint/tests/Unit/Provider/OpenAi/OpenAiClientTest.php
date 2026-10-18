<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\OpenAi;

use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\OpenAi\OpenAiClient
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(OpenAiClient::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(ProviderSettings::class)]
final class OpenAiClientTest extends TestCase
{
    public function testDecisionsPostsTheModelInputAndQuestions(): void
    {
        $transport = new class () implements Transport {
            /** @var list<HttpRequest> */
            public array $sent = [];
            public function send(array $requests): array
            {
                $this->sent = $requests;

                return [new HttpResponse(200, '{"answers":[]}')];
            }
        };
        $client = new OpenAiClient(new JsonEndpoint($transport, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna');
        $responses = $client->decisions([['input' => 'text', 'questions' => [['type' => 'predicate', 'name' => 'q', 'instructions' => 'Q?']]]]);
        self::assertSame([['answers' => []]], $responses);
        self::assertSame('https://api.openai.com/v1/decisions', $transport->sent[0]->url);
        self::assertSame('{"model":"gpt-6-luna","input":"text","questions":[{"type":"predicate","name":"q","instructions":"Q?"}]}', $transport->sent[0]->body);
    }
}
