<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Jev;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeJevTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeJevTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(JevClient::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeJevTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class JevClientTest extends TestCase
{
    public function testSystemOnePostsTheModelStateAndQuestions(): void
    {
        $transport = new FakeJevTransport(new AnswerScript(static fn (): Answer => Answer::predicate(0.3)), 'jev-1.13.0');
        $client = new JevClient(new JsonEndpoint($transport, ProviderSettings::defaults('jev'), 'k'), 'jev-latest');
        $responses = $client->systemOne([['state' => ['file' => 'a.php'], 'questions' => ['q' => ['type' => 'noul', 'instructions' => 'Q?']]]]);
        self::assertSame('jev-1.13.0', $responses[0]['model']);
        self::assertSame(['q' => ['type' => 'noul', 'noul' => 0.3]], $responses[0]['answers']);
        self::assertSame('https://api.typesafe.ai/v1/systemone', $transport->requests[0]->url);
        self::assertSame('{"model":"jev-latest","state":{"file":"a.php"},"questions":{"q":{"type":"noul","instructions":"Q?"}}}', $transport->requests[0]->body);
    }
}
