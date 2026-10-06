<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\AtxHeadingMatcher;
use Guard\Collect\Markdown\Parsing\BlockLineScanner;
use Guard\Collect\Markdown\Parsing\BlockMarkerMatcher;
use Guard\Collect\Markdown\Parsing\Fence;
use Guard\Collect\Markdown\Parsing\FenceMatcher;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use Guard\Collect\Markdown\Parsing\HtmlBlockMatcher;
use Guard\Collect\Markdown\Parsing\LineIndentation;
use Guard\Collect\Markdown\Parsing\LineScanner;
use Guard\Collect\Markdown\Parsing\ParserState;
use Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\LineScanner
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\BlockLineScanner
 * @uses \Guard\Collect\Markdown\Parsing\BlockMarkerMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Fence
 * @uses \Guard\Collect\Markdown\Parsing\FenceMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Markdown\Parsing\HtmlBlockMatcher
 * @uses \Guard\Collect\Markdown\Parsing\LineIndentation
 * @uses \Guard\Collect\Markdown\Parsing\ParserState
 * @uses \Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher
 */
#[CoversClass(LineScanner::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
final class LineScannerTest extends TestCase
{
    public function testScanIgnoresHeadingsInsideFence(): void
    {
        $state = new ParserState();
        $state->fence = new Fence('`', 3);
        $scanner = new LineScanner();

        self::assertNull($scanner->scan($state, '# comment', 2));
        self::assertNull($scanner->scan($state, '```', 3));
        self::assertNull($state->fence);
        self::assertSame('# Title', $scanner->scan($state, '# Title', 4)?->notation());
    }

    public function testScanIgnoresHeadingsInsideHtmlBlocks(): void
    {
        $state = new ParserState();
        $state->htmlEnd = '/-->/';
        $scanner = new LineScanner();

        self::assertNull($scanner->scan($state, '# inside comment', 2));
        self::assertNull($scanner->scan($state, '-->', 3));
        self::assertNull($state->htmlEnd);

        $state->htmlEnd = '';
        self::assertNull($scanner->scan($state, '# inside div', 5));
        self::assertNull($scanner->scan($state, '', 6));
        self::assertNull($state->htmlEnd);
        self::assertTrue($state->previousBlank);
    }

    public function testScanClosesParagraphOnBlankLine(): void
    {
        $state = new ParserState();
        $scanner = new LineScanner();
        $scanner->scan($state, 'text', 1);

        self::assertNull($scanner->scan($state, '   ', 2));
        self::assertSame([], $state->paragraph);
        self::assertNull($scanner->scan($state, '---', 3));
    }

    public function testScanNeverReadsIndentedCodeAsHeading(): void
    {
        $state = new ParserState();

        self::assertNull((new LineScanner())->scan($state, '    # indented code', 1));
        self::assertNull((new LineScanner())->scan($state, "\t# tab indented", 2));
        self::assertFalse($state->previousBlank);
    }
}
