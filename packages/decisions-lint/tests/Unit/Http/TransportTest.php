<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
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
        $transport = new class () implements Transport {
            public function send(array $requests): array
            {
                $responses = [];
                foreach ($requests as $request) {
                    $responses[] = new HttpResponse(200, $request->body);
                }

                return $responses;
            }
        };
        $responses = $transport->send([new HttpRequest('POST', 'https://a.test/', [], 'first'), new HttpRequest('POST', 'https://a.test/', [], 'second')]);
        self::assertSame('first', $responses[0]->body);
        self::assertSame('second', $responses[1]->body);
    }
}
