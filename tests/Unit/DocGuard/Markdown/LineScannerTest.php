<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\BlockLineScanner;
use Toolkit\DocGuard\Markdown\BlockMarkerMatcher;
use Toolkit\DocGuard\Markdown\Fence;
use Toolkit\DocGuard\Markdown\FenceMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;
use Toolkit\DocGuard\Markdown\HtmlBlockMatcher;
use Toolkit\DocGuard\Markdown\LineIndentation;
use Toolkit\DocGuard\Markdown\LineScanner;
use Toolkit\DocGuard\Markdown\ParserState;
use Toolkit\DocGuard\Markdown\SetextUnderlineMatcher;

/**
 * @covers \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
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
