<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown;

use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Block\BlockLineScanner;
use Guard\Structure\Markdown\BlockMarkerMatcher;
use Guard\Structure\Markdown\Fence;
use Guard\Structure\Markdown\FenceMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingParser;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\Markdown\HtmlBlockMatcher;
use Guard\Structure\Markdown\LineIndentation;
use Guard\Structure\Markdown\LineScanner;
use Guard\Structure\Markdown\MarkdownLineSplitter;
use Guard\Structure\Markdown\ParserState;
use Guard\Structure\Markdown\SetextUnderlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\HeadingParser
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\BlockMarkerMatcher
 * @uses \Guard\Structure\Markdown\Block\BlockLineScanner
 * @uses \Guard\Structure\Markdown\Fence
 * @uses \Guard\Structure\Markdown\FenceMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\HtmlBlockMatcher
 * @uses \Guard\Structure\Markdown\LineIndentation
 * @uses \Guard\Structure\Markdown\LineScanner
 * @uses \Guard\Structure\Markdown\MarkdownLineSplitter
 * @uses \Guard\Structure\Markdown\ParserState
 * @uses \Guard\Structure\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(HeadingParser::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
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
