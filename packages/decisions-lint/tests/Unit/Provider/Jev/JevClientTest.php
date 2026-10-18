<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Jev;

use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(JevClient::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(ProviderSettings::class)]
final class JevClientTest extends TestCase
{
    public function testSystemOnePostsTheModelStateAndQuestions(): void
    {
        $transport = new class () implements Transport {
            /** @var list<HttpRequest> */
            public array $sent = [];
            public function send(array $requests): array
            {
                $this->sent = $requests;

                return [new HttpResponse(200, '{"model":"jev-1.13.0","answers":{}}')];
            }
        };
        $client = new JevClient(new JsonEndpoint($transport, ProviderSettings::defaults('jev'), 'k'), 'jev-latest');
        $responses = $client->systemOne([['state' => ['file' => 'a.php'], 'questions' => ['q' => ['type' => 'noul', 'instructions' => 'Q?']]]]);
        self::assertSame([['model' => 'jev-1.13.0', 'answers' => []]], $responses);
        self::assertSame('https://api.typesafe.ai/v1/systemone', $transport->sent[0]->url);
        self::assertSame('{"model":"jev-latest","state":{"file":"a.php"},"questions":{"q":{"type":"noul","instructions":"Q?"}}}', $transport->sent[0]->body);
    }
}
