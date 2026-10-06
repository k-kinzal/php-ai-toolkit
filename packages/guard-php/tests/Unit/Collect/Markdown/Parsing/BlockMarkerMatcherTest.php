<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\BlockMarkerMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\BlockMarkerMatcher
 */
#[CoversClass(BlockMarkerMatcher::class)]
final class BlockMarkerMatcherTest extends TestCase
{
    public function testIsThematicBreakRecognizesBreaks(): void
    {
        self::assertTrue((new BlockMarkerMatcher())->isThematicBreak('***'));
        self::assertTrue((new BlockMarkerMatcher())->isThematicBreak('- - -'));
        self::assertTrue((new BlockMarkerMatcher())->isThematicBreak('___ '));
        self::assertFalse((new BlockMarkerMatcher())->isThematicBreak('--'));
        self::assertFalse((new BlockMarkerMatcher())->isThematicBreak('*-*'));
    }

    public function testIsListItemRecognizesBulletAndOrderedItems(): void
    {
        self::assertTrue((new BlockMarkerMatcher())->isListItem('- item'));
        self::assertTrue((new BlockMarkerMatcher())->isListItem('* item'));
        self::assertTrue((new BlockMarkerMatcher())->isListItem('12. item'));
        self::assertTrue((new BlockMarkerMatcher())->isListItem('3) item'));
        self::assertTrue((new BlockMarkerMatcher())->isListItem('-'));
        self::assertFalse((new BlockMarkerMatcher())->isListItem('-item'));
        self::assertFalse((new BlockMarkerMatcher())->isListItem('2024 was'));
    }

    public function testIsBlockQuoteRecognizesQuotes(): void
    {
        self::assertTrue((new BlockMarkerMatcher())->isBlockQuote('> # quoted'));
        self::assertFalse((new BlockMarkerMatcher())->isBlockQuote('text > more'));
    }
}
