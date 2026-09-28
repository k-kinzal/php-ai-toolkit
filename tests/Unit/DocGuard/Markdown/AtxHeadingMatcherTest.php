<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;

/**
 * @covers \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 */
#[CoversClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
final class AtxHeadingMatcherTest extends TestCase
{
    public function testMatchReadsLevelTextAndLine(): void
    {
        $heading = (new AtxHeadingMatcher())->match('###   Getting   Started', 12);

        self::assertNotNull($heading);
        self::assertSame(3, $heading->level);
        self::assertSame('Getting Started', $heading->text);
        self::assertSame(12, $heading->line);
    }

    public function testMatchRemovesClosingSequence(): void
    {
        self::assertSame('## Usage', (new AtxHeadingMatcher())->match('## Usage ##', 1)?->notation());
        self::assertSame('## Usage #5', (new AtxHeadingMatcher())->match('## Usage #5', 1)?->notation());
        self::assertSame('## C\#', (new AtxHeadingMatcher())->match('## C\#', 1)?->notation());
        self::assertSame('##', (new AtxHeadingMatcher())->match('## ###', 1)?->notation());
        self::assertSame('#', (new AtxHeadingMatcher())->match('#', 1)?->notation());
    }

    public function testMatchRejectsNonHeadings(): void
    {
        self::assertNull((new AtxHeadingMatcher())->match('#hashtag', 1));
        self::assertNull((new AtxHeadingMatcher())->match('####### seven', 1));
        self::assertNull((new AtxHeadingMatcher())->match('\# escaped', 1));
        self::assertNull((new AtxHeadingMatcher())->match('text # not', 1));
    }
}
