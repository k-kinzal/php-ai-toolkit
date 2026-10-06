<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown;

use Guard\Structure\Markdown\HtmlBlockMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\HtmlBlockMatcher
 */
#[CoversClass(HtmlBlockMatcher::class)]
final class HtmlBlockMatcherTest extends TestCase
{
    public function testStartReturnsTerminatorForRawAndCommentBlocks(): void
    {
        self::assertSame('/-->/', (new HtmlBlockMatcher())->start('<!-- NOTE', false));
        self::assertSame('#</(?:script|pre|style|textarea)>#i', (new HtmlBlockMatcher())->start('<pre>', true));
        self::assertSame('/\?>/', (new HtmlBlockMatcher())->start('<?php', false));
        self::assertSame('/\]\]>/', (new HtmlBlockMatcher())->start('<![CDATA[', false));
        self::assertSame('/>/', (new HtmlBlockMatcher())->start('<!DOCTYPE html', false));
    }

    public function testStartReturnsBlankLineForBlockTags(): void
    {
        self::assertSame(HtmlBlockMatcher::BLANK_LINE, (new HtmlBlockMatcher())->start('<details>', true));
        self::assertSame(HtmlBlockMatcher::BLANK_LINE, (new HtmlBlockMatcher())->start('<p align="center">', false));
        self::assertSame(HtmlBlockMatcher::BLANK_LINE, (new HtmlBlockMatcher())->start('<custom-tag data-x="1">', false));
    }

    public function testStartRejectsInlineHtmlAndTagsInsideParagraphs(): void
    {
        self::assertNull((new HtmlBlockMatcher())->start('<custom-tag>', true));
        self::assertNull((new HtmlBlockMatcher())->start('<span>text</span> more', false));
        self::assertNull((new HtmlBlockMatcher())->start('# Title', false));
    }

    public function testEndsMatchesTheTerminator(): void
    {
        self::assertTrue((new HtmlBlockMatcher())->ends('/-->/', 'end -->'));
        self::assertFalse((new HtmlBlockMatcher())->ends('/-->/', '# not a heading'));
        self::assertFalse((new HtmlBlockMatcher())->ends(HtmlBlockMatcher::BLANK_LINE, ''));
    }
}
