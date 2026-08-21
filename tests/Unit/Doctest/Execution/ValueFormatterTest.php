<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Execution\ValueFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ValueFormatter::class)]
final class ValueFormatterTest extends TestCase
{
    public function testFormatRendersScalarsTheWayTheyAreWrittenInSource(): void
    {
        $formatter = new ValueFormatter();

        self::assertSame("'done'", $formatter->format('done'));
        self::assertSame('3', $formatter->format(3));
        self::assertSame('1.5', $formatter->format(1.5));
        self::assertSame('true', $formatter->format(true));
        self::assertSame('false', $formatter->format(false));
        self::assertSame('null', $formatter->format(null));
    }

    public function testFormatRendersArraysAndObjectsCompactly(): void
    {
        $formatter = new ValueFormatter();

        self::assertStringContainsString('0 => 1', $formatter->format([1, 2]));
        self::assertSame('object(stdClass)', $formatter->format(new stdClass()));
    }
}
