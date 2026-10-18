<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\FakeTransport;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class TransportTest extends TestCase
{
    public function testSendReturnsOneResponsePerRequestInOrder(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, 'first'), new HttpResponse(200, 'second')]);
        $responses = $transport->send([new HttpRequest('POST', 'https://a.test/', [], '1'), new HttpRequest('POST', 'https://a.test/', [], '2')]);
        self::assertSame('first', $responses[0]->body);
        self::assertSame('second', $responses[1]->body);
        self::assertCount(2, $transport->requests);
    }
}
