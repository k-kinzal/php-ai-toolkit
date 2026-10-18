<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\FakeTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 */
#[CoversClass(FakeTransport::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
final class FakeTransportTest extends TestCase
{
    public function testSendReplaysResponsesInOrderAndRecordsRequests(): void
    {
        $first = new HttpResponse(200, 'one');
        $second = new HttpResponse(500, 'two');
        $transport = new FakeTransport([$first, $second]);
        $request = new HttpRequest('POST', 'https://a.test/x', [], '{}');
        self::assertSame([$first], $transport->send([$request]));
        self::assertSame([$second], $transport->send([$request]));
        self::assertSame([$request, $request], $transport->requests);
    }

    public function testSendRefusesMoreRequestsThanResponses(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('FakeTransport has no response left for POST https://a.test/x. Give it one HttpResponse per expected request.');
        (new FakeTransport())->send([new HttpRequest('POST', 'https://a.test/x', [], '{}')]);
    }
}
