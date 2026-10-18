<?php

declare(strict_types=1);

namespace Tests\Unit\Provider;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(JsonEndpoint::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(FakeTransport::class)]
#[UsesClass(ProviderSettings::class)]
final class JsonEndpointTest extends TestCase
{
    public function testPostSendsEveryPayloadAndDecodesTheResponsesInOrder(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, '{"index":0}'), new HttpResponse(200, '{"index":1}')]);
        $endpoint = new JsonEndpoint($transport, ProviderSettings::defaults('jev'), 'secret');
        $documents = $endpoint->post('/v1/systemone', [['a' => 1], ['a' => 2]]);
        self::assertSame([['index' => 0], ['index' => 1]], $documents);
        self::assertCount(2, $transport->requests);
        self::assertSame('https://api.typesafe.ai/v1/systemone', $transport->requests[0]->url);
        self::assertSame('{"a":2}', $transport->requests[1]->body);
    }

    public function testRequestCarriesTheBearerTokenAndJsonHeaders(): void
    {
        $request = (new JsonEndpoint(new FakeTransport(), new ProviderSettings('openai', 'gpt-6-luna', 'OPENAI_API_KEY', 'https://proxy.test/'), 'k'))->request('/v1/decisions', ['model' => 'gpt-6-luna', 'text' => 'ü/']);
        self::assertSame('POST', $request->method);
        self::assertSame('https://proxy.test/v1/decisions', $request->url);
        self::assertSame('Bearer k', $request->headers['Authorization']);
        self::assertSame('application/json', $request->headers['Content-Type']);
        self::assertSame('{"model":"gpt-6-luna","text":"ü/"}', $request->body);
    }

    public function testRequestOmitsTheAuthorizationHeaderWithoutAKey(): void
    {
        $request = (new JsonEndpoint(new FakeTransport(), ProviderSettings::defaults('ollama'), ''))->request('/api/chat', ['model' => 'llama3.1']);
        self::assertArrayNotHasKey('Authorization', $request->headers);
        self::assertSame('application/json', $request->headers['Accept']);
    }

    public function testRequestRejectsAPayloadThatCannotBeEncoded(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Could not encode a request for jev');
        (new JsonEndpoint(new FakeTransport(), ProviderSettings::defaults('jev'), 'k'))->request('/v1/systemone', ['bad' => "\xB1\x31"]);
    }

    public function testDecodeReturnsTheObjectOfASuccessfulResponse(): void
    {
        $endpoint = new JsonEndpoint(new FakeTransport(), ProviderSettings::defaults('jev'), 'k');
        $request = new HttpRequest('POST', 'https://api.typesafe.ai/v1/systemone', [], '{}');
        self::assertSame(['model' => 'jev-1.13.0'], $endpoint->decode(new HttpResponse(200, '{"model":"jev-1.13.0"}'), $request));
    }

    /**
     * @dataProvider providerDecodeFailures
     */
    #[DataProvider('providerDecodeFailures')]
    public function testDecodeExplainsFailures(HttpResponse $response, string $expected): void
    {
        $endpoint = new JsonEndpoint(new FakeTransport(), ProviderSettings::defaults('jev'), 'k');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage($expected);
        $endpoint->decode($response, new HttpRequest('POST', 'https://api.typesafe.ai/v1/systemone', [], '{}'));
    }

    /**
     * @return iterable<string, array{HttpResponse, string}>
     */
    public static function providerDecodeFailures(): iterable
    {
        yield 'unauthorized' => [new HttpResponse(401, '{"error":{"message":"bad key"}}'), 'jev returned HTTP 401 for POST https://api.typesafe.ai/v1/systemone: bad key. Check that the TYPESAFE_API_KEY environment variable holds a valid jev API key.'];
        yield 'not json' => [new HttpResponse(200, '<html>'), 'with a body that is not JSON'];
        yield 'json scalar' => [new HttpResponse(200, '"x"'), 'with a JSON body that is not an object'];
        yield 'rate limit without json' => [new HttpResponse(429, 'slow down'), 'jev returned HTTP 429 for POST https://api.typesafe.ai/v1/systemone: slow down. Lower provider.concurrency'];
    }

    /**
     * @dataProvider providerFailureHints
     */
    #[DataProvider('providerFailureHints')]
    public function testFailureAddsTheHintForTheStatus(int $status, string $hint): void
    {
        $endpoint = new JsonEndpoint(new FakeTransport(), ProviderSettings::defaults('openai'), 'k');
        $message = $endpoint->failure(new HttpResponse($status, ''), new HttpRequest('POST', 'https://api.openai.com/v1/decisions', [], '{}'), []);
        self::assertStringContainsString($hint, $message);
        self::assertStringStartsWith(sprintf('openai returned HTTP %d for POST https://api.openai.com/v1/decisions. ', $status), $message);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function providerFailureHints(): iterable
    {
        yield 'forbidden' => [403, 'OPENAI_API_KEY'];
        yield 'not found' => [404, 'provider.base_url'];
        yield 'unprocessable' => [422, 'provider.model ("gpt-6-luna")'];
        yield 'bad request' => [400, 'provider.model'];
        yield 'rate limited' => [429, 'provider.concurrency'];
        yield 'server error' => [503, 'retry later'];
        yield 'other' => [418, 'Check the provider status'];
    }

    public function testDetailPrefersTheErrorMessageAndFallsBackToAnExcerpt(): void
    {
        $endpoint = new JsonEndpoint(new FakeTransport(), ProviderSettings::defaults('jev'), 'k');
        self::assertSame('nope', $endpoint->detail(['error' => 'nope'], ''));
        self::assertSame('nested', $endpoint->detail(['error' => ['detail' => 'nested']], ''));
        self::assertSame('plain', $endpoint->detail(['message' => 'plain'], ''));
        self::assertSame('a b', $endpoint->detail([], "a\n\n  b"));
        self::assertSame(str_repeat('x', 200) . '…', $endpoint->detail([], str_repeat('x', 300)));
    }
}
