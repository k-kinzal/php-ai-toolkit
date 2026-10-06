<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Block;

use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Block\BlockLineScanner;
use Guard\Structure\Markdown\BlockMarkerMatcher;
use Guard\Structure\Markdown\Fence;
use Guard\Structure\Markdown\FenceMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\Markdown\HtmlBlockMatcher;
use Guard\Structure\Markdown\ParserState;
use Guard\Structure\Markdown\SetextUnderlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Block\BlockLineScanner
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\BlockMarkerMatcher
 * @uses \Guard\Structure\Markdown\Fence
 * @uses \Guard\Structure\Markdown\FenceMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\HtmlBlockMatcher
 * @uses \Guard\Structure\Markdown\ParserState
 * @uses \Guard\Structure\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(BlockLineScanner::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
final class BlockLineScannerTest extends TestCase
{
    public function testScanReturnsAtxHeadingAndClosesParagraph(): void
    {
        $state = new ParserState();
        $state->paragraph = ['text'];

        $heading = (new BlockLineScanner())->scan($state, '## Usage', 0, 4);

        self::assertSame('## Usage', $heading?->notation());
        self::assertSame(4, $heading->line);
        self::assertSame([], $state->paragraph);
    }

    public function testScanTurnsParagraphIntoSetextHeading(): void
    {
        $state = new ParserState();
        $scanner = new BlockLineScanner();
        $scanner->scan($state, 'Multi', 0, 3);
        $state->previousBlank = false;
        $scanner->scan($state, 'line   title', 0, 4);

        $heading = $scanner->scan($state, '===', 0, 5);

        self::assertSame('# Multi line title', $heading?->notation());
        self::assertSame(3, $heading->line);
    }

    public function testScanOpensFenceAndHtmlBlocks(): void
    {
        $state = new ParserState();
        $scanner = new BlockLineScanner();

        self::assertNull($scanner->scan($state, '~~~md', 0, 1));
        self::assertSame('~', $state->fence?->marker);

        $state->fence = null;
        self::assertNull($scanner->scan($state, '<!-- one line -->', 0, 2));
        self::assertNull($state->htmlEnd);
        self::assertNull($scanner->scan($state, '<details>', 0, 3));
        self::assertSame('', $state->htmlEnd);
    }

    public function testScanTreatsDashesWithoutParagraphAsThematicBreak(): void
    {
        $state = new ParserState();

        self::assertNull((new BlockLineScanner())->scan($state, '---', 0, 1));
        self::assertNull((new BlockLineScanner())->scan($state, '> quote', 0, 2));
        self::assertSame([], $state->paragraph);
    }

    public function testScanDoesNotTurnListItemsIntoSetextHeadings(): void
    {
        $state = new ParserState();
        $scanner = new BlockLineScanner();
        $scanner->scan($state, '- item', 0, 1);
        $state->previousBlank = false;

        self::assertTrue($state->listContext);
        self::assertNull($scanner->scan($state, '---', 0, 2));
    }

    public function testScanParagraphStartsAndContinuesParagraph(): void
    {
        $state = new ParserState();
        $scanner = new BlockLineScanner();
        $scanner->scanParagraph($state, 'first', 0, 7);
        $scanner->scanParagraph($state, 'second', 0, 8);

        self::assertSame(['first', 'second'], $state->paragraph);
        self::assertSame(7, $state->paragraphLine);
    }

    public function testScanParagraphKeepsListContinuationOutOfParagraphs(): void
    {
        $state = new ParserState();
        $state->listContext = true;
        $state->previousBlank = false;
        (new BlockLineScanner())->scanParagraph($state, 'lazy continuation', 0, 2);

        self::assertSame([], $state->paragraph);

        $state->previousBlank = true;
        (new BlockLineScanner())->scanParagraph($state, 'item content', 2, 3);

        self::assertSame([], $state->paragraph);
        self::assertTrue($state->listContext);
    }

    public function testScanParagraphLeavesListAfterBlankLineAtColumnZero(): void
    {
        $state = new ParserState();
        $state->listContext = true;
        $state->previousBlank = true;

        (new BlockLineScanner())->scanParagraph($state, 'after the list', 0, 5);

        self::assertFalse($state->listContext);
        self::assertSame(['after the list'], $state->paragraph);
    }

    public function testScanIndentedContinuesParagraph(): void
    {
        $state = new ParserState();
        $state->paragraph = ['text'];

        (new BlockLineScanner())->scanIndented($state, '    continued');

        self::assertSame(['text', 'continued'], $state->paragraph);
    }

    public function testScanIndentedOpensFenceOnlyInsideLists(): void
    {
        $state = new ParserState();
        (new BlockLineScanner())->scanIndented($state, '    ```');

        self::assertNull($state->fence);

        $state->listContext = true;
        (new BlockLineScanner())->scanIndented($state, '    ```bash');

        self::assertSame('`', $state->fence?->marker);
    }
}
