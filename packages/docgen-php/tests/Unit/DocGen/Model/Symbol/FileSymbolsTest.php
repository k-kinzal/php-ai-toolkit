<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Symbol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Symbol\FileSymbols;

/**
 * @covers \Toolkit\DocGen\Model\Symbol\FileSymbols
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
