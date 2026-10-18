<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use DecisionsLint\Http\HttpResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Http\HttpResponse
 */
#[CoversClass(HttpResponse::class)]
final class HttpResponseTest extends TestCase
{
    public function testOkAcceptsOnlyTheSuccessRange(): void
    {
        self::assertTrue((new HttpResponse(200, '{}'))->ok());
        self::assertTrue((new HttpResponse(299, ''))->ok());
        self::assertFalse((new HttpResponse(199, ''))->ok());
        self::assertFalse((new HttpResponse(429, 'slow down'))->ok());
        $response = new HttpResponse(500, 'oops');
        self::assertSame(500, $response->status);
        self::assertSame('oops', $response->body);
    }
}
