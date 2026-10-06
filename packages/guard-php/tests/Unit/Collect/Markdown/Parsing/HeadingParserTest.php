<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\AtxHeadingMatcher;
use Guard\Collect\Markdown\Parsing\BlockLineScanner;
use Guard\Collect\Markdown\Parsing\BlockMarkerMatcher;
use Guard\Collect\Markdown\Parsing\Fence;
use Guard\Collect\Markdown\Parsing\FenceMatcher;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Markdown\Parsing\HeadingParser;
use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use Guard\Collect\Markdown\Parsing\HtmlBlockMatcher;
use Guard\Collect\Markdown\Parsing\LineIndentation;
use Guard\Collect\Markdown\Parsing\LineScanner;
use Guard\Collect\Markdown\Parsing\MarkdownLineSplitter;
use Guard\Collect\Markdown\Parsing\ParserState;
use Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\HeadingParser
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\BlockLineScanner
 * @uses \Guard\Collect\Markdown\Parsing\BlockMarkerMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Fence
 * @uses \Guard\Collect\Markdown\Parsing\FenceMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Markdown\Parsing\HtmlBlockMatcher
 * @uses \Guard\Collect\Markdown\Parsing\LineIndentation
 * @uses \Guard\Collect\Markdown\Parsing\LineScanner
 * @uses \Guard\Collect\Markdown\Parsing\MarkdownLineSplitter
 * @uses \Guard\Collect\Markdown\Parsing\ParserState
 * @uses \Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher
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
