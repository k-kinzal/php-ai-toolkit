<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Provider\Payload
 */
#[CoversClass(RequestBody::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(Payload::class)]
final class RequestBodyTest extends TestCase
{
    public function testReadDecodesAPostToTheExpectedPath(): void
    {
        $payload = (new RequestBody())->read(new HttpRequest('POST', 'https://a.test/v1/systemone', [], '{"model":"jev-latest"}'), '/v1/systemone', 'Jev');
        self::assertSame('jev-latest', $payload->string('model'));
    }

    public function testReadRejectsTheWrongPath(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev fake accepts only POST /v1/systemone, not POST https://a.test/other.');
        (new RequestBody())->read(new HttpRequest('POST', 'https://a.test/other', [], '{}'), '/v1/systemone', 'Jev');
    }

    public function testReadRejectsABodyThatIsNotJson(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev fake received a body that is not JSON');
        (new RequestBody())->read(new HttpRequest('POST', 'https://a.test/v1/systemone', [], '{'), '/v1/systemone', 'Jev');
    }

    public function testReadRejectsAJsonBodyThatIsNotAnObject(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev fake received a JSON body that is not an object.');
        (new RequestBody())->read(new HttpRequest('POST', 'https://a.test/v1/systemone', [], '"x"'), '/v1/systemone', 'Jev');
    }

    public function testStringsReadsTheStringValuesOfAMapping(): void
    {
        $body = new RequestBody();
        self::assertSame(['file' => 'a.php', 'count' => ''], $body->strings(new Payload(['state' => ['file' => 'a.php', 'count' => 3]], 'ctx'), 'state'));
        self::assertSame([], $body->strings(new Payload([], 'ctx'), 'state'));
    }

    public function testEncodeWritesCompactJson(): void
    {
        self::assertSame('{"a":"ü/"}', (new RequestBody())->encode(['a' => 'ü/']));
    }

    public function testEncodeRejectsInvalidText(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('A fake provider could not encode its response');
        (new RequestBody())->encode(['a' => "\xB1\x31"]);
    }
}
