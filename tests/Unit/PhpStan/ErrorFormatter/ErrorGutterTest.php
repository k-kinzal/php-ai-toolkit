<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorGutter;
use PHPStan\Analyser\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorGutter::class)]
final class ErrorGutterTest extends TestCase
{
    public function testWidthReturnsAtLeastThreeCharacters(): void
    {
        $gutter = new ErrorGutter();

        self::assertSame(3, $gutter->width([
            new Error('Error', '/tmp/Foo.php', 7),
        ]));
    }

    public function testLinePadsLineNumberToRequestedWidth(): void
    {
        $gutter = new ErrorGutter();

        self::assertSame('  7', $gutter->line('7', 3));
    }
}
