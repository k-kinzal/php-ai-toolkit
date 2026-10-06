<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown;

use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Block\BlockLineScanner;
use Guard\Structure\Markdown\BlockMarkerMatcher;
use Guard\Structure\Markdown\Fence;
use Guard\Structure\Markdown\FenceMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\Markdown\HtmlBlockMatcher;
use Guard\Structure\Markdown\LineIndentation;
use Guard\Structure\Markdown\LineScanner;
use Guard\Structure\Markdown\ParserState;
use Guard\Structure\Markdown\SetextUnderlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\LineScanner
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\BlockMarkerMatcher
 * @uses \Guard\Structure\Markdown\Block\BlockLineScanner
 * @uses \Guard\Structure\Markdown\Fence
 * @uses \Guard\Structure\Markdown\FenceMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\HtmlBlockMatcher
 * @uses \Guard\Structure\Markdown\LineIndentation
 * @uses \Guard\Structure\Markdown\ParserState
 * @uses \Guard\Structure\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(LineScanner::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
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
