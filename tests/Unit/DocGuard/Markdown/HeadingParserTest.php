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
use Toolkit\DocGuard\Markdown\HeadingParser;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;
use Toolkit\DocGuard\Markdown\HtmlBlockMatcher;
use Toolkit\DocGuard\Markdown\LineIndentation;
use Toolkit\DocGuard\Markdown\LineScanner;
use Toolkit\DocGuard\Markdown\MarkdownLineSplitter;
use Toolkit\DocGuard\Markdown\ParserState;
use Toolkit\DocGuard\Markdown\SetextUnderlineMatcher;

/**
 * @covers \Toolkit\DocGuard\Markdown\HeadingParser
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(HeadingParser::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
final class HeadingParserTest extends TestCase
{
    public function testParseReturnsAtxAndSetextHeadingsInOrder(): void
    {
        $headings = (new HeadingParser())->parse(<<<'MARKDOWN'
Project
=======

## Requirements ##

Paragraph
spanning lines
--------------

### Details
MARKDOWN);

        self::assertSame(
            [['# Project', 1], ['## Requirements', 4], ['## Paragraph spanning lines', 6], ['### Details', 10]],
            array_map(static fn (Heading $heading): array => [$heading->notation(), $heading->line], $headings),
        );
    }

    public function testParseIgnoresHashLinesInBacktickAndTildeFences(): void
    {
        $headings = (new HeadingParser())->parse(<<<'MARKDOWN'
# Title

```bash
# install
composer install
```

~~~~markdown
## Example heading
```
### still inside
~~~~

## Usage
MARKDOWN);

        self::assertSame(['# Title', '## Usage'], array_map(static fn (Heading $heading): string => $heading->notation(), $headings));
    }

    public function testParseIgnoresFencesInListItemsCommentsAndFrontMatter(): void
    {
        $headings = (new HeadingParser())->parse(<<<'MARKDOWN'
---
title: Guide
---
<!-- NOTE: managed by a human -->
<!--
# commented out
-->
# Guide

1. Install:

    ```bash
# comment inside the fence
    ```

- item
---

> # quoted

    # indented code

## Next
MARKDOWN);

        self::assertSame(['# Guide', '## Next'], array_map(static fn (Heading $heading): string => $heading->notation(), $headings));
    }

    public function testParseTreatsUnclosedFenceAsRunningToTheEnd(): void
    {
        $headings = (new HeadingParser())->parse("# Title\n\n```\n# not a heading\n");

        self::assertSame(['# Title'], array_map(static fn (Heading $heading): string => $heading->notation(), $headings));
    }
}
