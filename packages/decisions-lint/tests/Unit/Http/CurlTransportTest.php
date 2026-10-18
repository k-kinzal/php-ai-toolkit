<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\CurlTransport;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Http\CurlTransport
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 */
#[CoversClass(CurlTransport::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
final class CurlTransportTest extends TestCase
{
    public function testSendReturnsNothingForNoRequests(): void
    {
        self::assertSame([], (new CurlTransport(2, 1))->send([]));
    }

    public function testSendExplainsAnUnreachableHost(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Could not reach http://127.0.0.1:9/v1/systemone');
        (new CurlTransport(1, 1))->send([new HttpRequest('POST', 'http://127.0.0.1:9/v1/systemone', ['Content-Type' => 'application/json'], '{}')]);
    }

    public function testSendChunkReturnsNothingForNoRequests(): void
    {
        self::assertSame([], (new CurlTransport())->sendChunk([]));
    }

    public function testHandleConfiguresTheUrl(): void
    {
        $handle = (new CurlTransport())->handle(new HttpRequest('POST', 'https://api.example.test/v1/x', ['Accept' => 'application/json'], '{}'));
        self::assertSame('https://api.example.test/v1/x', curl_getinfo($handle, CURLINFO_EFFECTIVE_URL));
    }

    public function testDriveReturnsNoResultsWhenNothingIsInFlight(): void
    {
        $multi = curl_multi_init();
        self::assertSame([], (new CurlTransport())->drive($multi));
        curl_multi_close($multi);
    }

    public function testResponseRejectsAHandleThatNeverGotAResponse(): void
    {
        $request = new HttpRequest('POST', 'https://api.example.test/v1/x', [], '{}');
        $transport = new CurlTransport();
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('no response');
        $transport->response($transport->handle($request), $request, CURLE_OK);
    }

    public function testResponseNamesTheTransferFailure(): void
    {
        $request = new HttpRequest('POST', 'https://api.example.test/v1/x', [], '{}');
        $transport = new CurlTransport();
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Could not reach https://api.example.test/v1/x: ' . curl_strerror(CURLE_COULDNT_CONNECT));
        $transport->response($transport->handle($request), $request, CURLE_COULDNT_CONNECT);
    }
}
