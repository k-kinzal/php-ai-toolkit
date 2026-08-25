<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols
 */
#[CoversClass(FileSymbols::class)]
final class FileSymbolsTest extends TestCase
{
    public function testStoresCollectedSymbols(): void
    {
        $symbols = new FileSymbols([], []);

        self::assertSame([], $symbols->classLikes);
        self::assertSame([], $symbols->functions);
    }
}
