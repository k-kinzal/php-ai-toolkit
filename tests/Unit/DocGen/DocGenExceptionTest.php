<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen;

use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
