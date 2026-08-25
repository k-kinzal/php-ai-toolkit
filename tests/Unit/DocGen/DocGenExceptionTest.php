<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\DocGenException
 */
#[CoversClass(DocGenException::class)]
final class DocGenExceptionTest extends TestCase
{
    public function testCarriesMessage(): void
    {
        $exception = new DocGenException('Unknown option: --bogus');

        self::assertSame('Unknown option: --bogus', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }
}
