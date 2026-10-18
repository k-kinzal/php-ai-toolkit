<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use DecisionsLint\Http\HttpRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Http\HttpRequest
 */
#[CoversClass(HttpRequest::class)]
final class HttpRequestTest extends TestCase
{
    public function testExposesTheEncodedRequest(): void
    {
        $request = new HttpRequest('POST', 'https://api.example.test/v1/x', ['Accept' => 'application/json'], '{"a":1}');
        self::assertSame('POST', $request->method);
        self::assertSame('https://api.example.test/v1/x', $request->url);
        self::assertSame(['Accept' => 'application/json'], $request->headers);
        self::assertSame('{"a":1}', $request->body);
    }
}
