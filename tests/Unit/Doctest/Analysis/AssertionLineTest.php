<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\AssertionLine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertionLine::class)]
final class AssertionLineTest extends TestCase
{
    public function testStoresAssertionData(): void
    {
        $line = new AssertionLine('  $f->run(); // throws E: boom', '$f->run();', 'throws', 'E', 'boom');

        self::assertSame('  $f->run(); // throws E: boom', $line->text);
        self::assertSame('$f->run();', $line->code);
        self::assertSame('throws', $line->marker);
        self::assertSame('E', $line->expected);
        self::assertSame('boom', $line->exceptionMessage);
    }

    public function testStoresSmokeLineWithNullMarkerData(): void
    {
        $line = new AssertionLine('$sum = 1 + 2;', '$sum = 1 + 2;', null, null, null);

        self::assertSame('$sum = 1 + 2;', $line->text);
        self::assertSame('$sum = 1 + 2;', $line->code);
        self::assertNull($line->marker);
        self::assertNull($line->expected);
        self::assertNull($line->exceptionMessage);
    }
}
