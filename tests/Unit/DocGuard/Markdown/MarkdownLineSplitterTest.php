<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\MarkdownLineSplitter;

/**
 * @covers \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 */
#[CoversClass(MarkdownLineSplitter::class)]
final class MarkdownLineSplitterTest extends TestCase
{
    public function testSplitHandlesEveryLineTerminator(): void
    {
        self::assertSame(['a', 'b', 'c', 'd'], (new MarkdownLineSplitter())->split("a\r\nb\nc\rd"));
    }

    public function testSplitRemovesByteOrderMark(): void
    {
        self::assertSame(['# Title', ''], (new MarkdownLineSplitter())->split("\xEF\xBB\xBF# Title\n"));
    }

    public function testFrontMatterLengthCountsClosedFrontMatter(): void
    {
        self::assertSame(3, (new MarkdownLineSplitter())->frontMatterLength(['---', 'name: x', '---', '# Title']));
        self::assertSame(2, (new MarkdownLineSplitter())->frontMatterLength(['---', '...', '# Title']));
    }

    public function testFrontMatterLengthIgnoresUnclosedOrMissingFrontMatter(): void
    {
        self::assertSame(0, (new MarkdownLineSplitter())->frontMatterLength(['---', 'text']));
        self::assertSame(0, (new MarkdownLineSplitter())->frontMatterLength(['# Title', '---']));
        self::assertSame(0, (new MarkdownLineSplitter())->frontMatterLength([]));
    }
}
