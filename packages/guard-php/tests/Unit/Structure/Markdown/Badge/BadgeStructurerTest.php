<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown\Badge;

use Guard\Policy\PolicyException;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Badge\BadgeBlock;
use Guard\Structure\Markdown\Badge\BadgeLineParser;
use Guard\Structure\Markdown\Badge\BadgeStructurer;
use Guard\Structure\Markdown\Block\BlockLineScanner;
use Guard\Structure\Markdown\BlockMarkerMatcher;
use Guard\Structure\Markdown\Fence;
use Guard\Structure\Markdown\FenceMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use Guard\Structure\Markdown\HeadingParser;
use Guard\Structure\Markdown\HeadingStructurer;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\Markdown\HtmlBlockMatcher;
use Guard\Structure\Markdown\LineIndentation;
use Guard\Structure\Markdown\LineScanner;
use Guard\Structure\Markdown\MarkdownLineSplitter;
use Guard\Structure\Markdown\ParserState;
use Guard\Structure\Markdown\SetextUnderlineMatcher;
use Guard\Structure\Source;
use Guard\Structure\TextStructurer;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\Badge\BadgeStructurer
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Badge\Badge
 * @uses \Guard\Structure\Markdown\Badge\BadgeBlock
 * @uses \Guard\Structure\Markdown\Badge\BadgeLineParser
 * @uses \Guard\Structure\Markdown\Block\BlockLineScanner
 * @uses \Guard\Structure\Markdown\BlockMarkerMatcher
 * @uses \Guard\Structure\Markdown\Fence
 * @uses \Guard\Structure\Markdown\FenceMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingList
 * @uses \Guard\Structure\Markdown\HeadingParser
 * @uses \Guard\Structure\Markdown\HeadingStructurer
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\HtmlBlockMatcher
 * @uses \Guard\Structure\Markdown\LineIndentation
 * @uses \Guard\Structure\Markdown\LineScanner
 * @uses \Guard\Structure\Markdown\MarkdownLineSplitter
 * @uses \Guard\Structure\Markdown\ParserState
 * @uses \Guard\Structure\Markdown\SetextUnderlineMatcher
 * @uses \Guard\Structure\Source
 * @uses \Guard\Structure\Text
 * @uses \Guard\Structure\TextStructurer
 */
#[CoversClass(BadgeStructurer::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Badge::class)]
#[UsesClass(BadgeBlock::class)]
#[UsesClass(BadgeLineParser::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingList::class)]
#[UsesClass(HeadingParser::class)]
#[UsesClass(HeadingStructurer::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
#[UsesClass(Source::class)]
#[UsesClass(\Guard\Structure\Text::class)]
#[UsesClass(TextStructurer::class)]
final class BadgeStructurerTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureReadsTheFirstBadgeRunBelowTheTitle(): void
    {
        $block = (new BadgeStructurer())->structure(new Source("# Tool\n\n[![PHP](https://img.shields.io/badge/php-8.1)](https://www.php.net/)\n[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)\n\n[![Late](https://example.com/late.svg)](https://example.com/)\n", ['markdown.headings' => new HeadingStructurer()]));

        self::assertNotNull($block->title);
        self::assertSame('Tool', $block->title->text);
        self::assertSame(['PHP', 'License'], array_map(static fn (Badge $badge): string => $badge->label, $block->badges));
        self::assertSame([3, 4], array_map(static fn (Badge $badge): int => $badge->line, $block->badges));
        self::assertNull($block->earlyLine);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureSkipsASetextUnderline(): void
    {
        $block = (new BadgeStructurer())->structure(new Source("Tool\n====\n[![PHP](https://img.shields.io/badge/php-8.1)](https://www.php.net/)\n", ['markdown.headings' => new HeadingStructurer()]));

        self::assertCount(1, $block->badges);
        self::assertSame(3, $block->badges[0]->line);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureRecordsBadgesAboveTheTitle(): void
    {
        $block = (new BadgeStructurer())->structure(new Source("[![PHP](https://img.shields.io/badge/php-8.1)](https://www.php.net/)\n\n# Tool\n\nOverview.\n", ['markdown.headings' => new HeadingStructurer()]));

        self::assertSame([], $block->badges);
        self::assertSame(1, $block->earlyLine);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureWithoutTitleKeepsOnlyTheEarlyLine(): void
    {
        $block = (new BadgeStructurer())->structure(new Source("Intro\n\n[![PHP](https://img.shields.io/badge/php-8.1)](https://www.php.net/)\n## Usage\n", ['markdown.headings' => new HeadingStructurer()]));

        self::assertNull($block->title);
        self::assertSame([], $block->badges);
        self::assertSame(3, $block->earlyLine);
    }

    public function testBodyStartFallsBackToTheNextLineWithoutAnUnderline(): void
    {
        self::assertSame(1, (new BadgeStructurer())->bodyStart(['Tool'], new Heading(1, 'Tool', 1)));
    }

    public function testBadgesStopsAtTheFirstNonBadgeLine(): void
    {
        $badges = (new BadgeStructurer())->badges(['', 'Overview.', '[![PHP](https://img.shields.io/badge/php-8.1)](https://www.php.net/)'], 0);

        self::assertSame([], $badges);
    }

    public function testEarlyLineIsNullWithoutBadges(): void
    {
        self::assertNull((new BadgeStructurer())->earlyLine(['Intro', ''], 2));
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureRequiresAHeadingList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('BadgeStructurer requires markdown.headings to return HeadingList.');

        (new BadgeStructurer())->structure(new Source('# Tool', ['markdown.headings' => new TextStructurer()]));
    }
}
