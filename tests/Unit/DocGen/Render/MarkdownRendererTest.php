<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownLinks;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\MarkdownRenderer
 * @uses \PhpAiToolkit\DocGen\Render\HtmlText
 * @uses \PhpAiToolkit\DocGen\Render\MarkdownInline
 * @uses \PhpAiToolkit\DocGen\Render\MarkdownLinks
 * @uses \PhpAiToolkit\DocGen\Render\SiteUrl
 */
#[CoversClass(MarkdownRenderer::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownLinks::class)]
#[UsesClass(SiteUrl::class)]
final class MarkdownRendererTest extends TestCase
{
    public function testWithLinksResolvesDocumentLinksWithoutChangingTheOriginal(): void
    {
        $renderer = new MarkdownRenderer();
        $links = new MarkdownLinks(new SiteUrl(), 'demo/pkg', 'demo/pkg/index.html', '', ['docs/guide.md']);

        self::assertSame(
            '<p>See <a href="../../demo/pkg/doc/docs/guide.md.html">the guide</a>.</p>' . "\n",
            $renderer->withLinks($links)->render('See [the guide](docs/guide.md).'),
        );
        self::assertSame(
            '<p>See <span class="md-target" title="docs/guide.md">the guide</span>.</p>' . "\n",
            $renderer->render('See [the guide](docs/guide.md).'),
        );
    }

    public function testRenderBuildsHeadingsRuleAndParagraphs(): void
    {
        $expected = <<<'HTML'
<h2>Title</h2>
<p>Some <strong>para</strong> text more</p>
<hr>
<h3>Sub</h3>
HTML;

        self::assertSame($expected . "\n", (new MarkdownRenderer())->render("# Title\n\nSome **para** text\nmore\n\n---\n\n## Sub ##\n"));
    }

    public function testRenderEscapesFencedCodeByDefault(): void
    {
        self::assertSame(
            '<pre class="code-block"><code>&lt;code&gt; &amp; stuff</code></pre>' . "\n",
            (new MarkdownRenderer())->render("```\n<code> & stuff\n```\n"),
        );
    }

    public function testRenderUsesCustomFenceClosure(): void
    {
        $html = (new MarkdownRenderer())->render(
            "```php\necho 1;\n```\n",
            static fn (string $code, string $language): string => '<x lang="' . $language . '">' . $code . '</x>',
        );

        self::assertSame('<x lang="php">echo 1;</x>', $html);
    }

    public function testRenderFallsBackWhenFenceClosureReturnsNull(): void
    {
        $html = (new MarkdownRenderer())->render(
            "```text\n<x>\n```\n",
            static fn (string $code, string $language): ?string => $language === 'php' ? '<x>' . $code . '</x>' : null,
        );

        self::assertSame('<pre class="code-block"><code>&lt;x&gt;</code></pre>' . "\n", $html);
    }

    public function testRenderBuildsBlockquoteAfterParagraph(): void
    {
        $expected = <<<'HTML'
<p>Intro para</p>
<blockquote><p>note here</p>
</blockquote>
HTML;

        self::assertSame($expected . "\n", (new MarkdownRenderer())->render("Intro para\n\n> note here\n"));
    }

    public function testRenderBuildsNestedUnorderedList(): void
    {
        self::assertSame(
            '<ul><li>one</li><li>two<ul><li>sub</li></ul></li><li>three cont line</li></ul>' . "\n",
            (new MarkdownRenderer())->render("- one\n- two\n  - sub\n- three\n  cont line\n"),
        );
    }

    public function testRenderBuildsOrderedList(): void
    {
        self::assertSame('<ol><li>first</li><li>second</li></ol>' . "\n", (new MarkdownRenderer())->render("1. first\n2. second\n"));
    }

    public function testRenderBuildsPipeTable(): void
    {
        self::assertSame(
            '<div class="table-wrap"><table><thead><tr><th>A</th><th>B|C</th></tr></thead><tbody><tr><td>1</td><td>2</td></tr></tbody></table></div>' . "\n",
            (new MarkdownRenderer())->render("| A | B\\|C |\n|---|---|\n| 1 | 2 |\n"),
        );
    }

    public function testFenceBlockEscapesCodeByDefault(): void
    {
        self::assertSame(
            ['<pre class="code-block"><code>&lt;x&gt;</code></pre>' . "\n", 3],
            (new MarkdownRenderer())->fenceBlock(['```', '<x>', '```'], 0, null),
        );
    }

    public function testFenceBlockPassesCodeAndLanguageToClosure(): void
    {
        self::assertSame(
            ['<x lang="php">echo 1;</x>', 3],
            (new MarkdownRenderer())->fenceBlock(
                ['```php', 'echo 1;', '```'],
                0,
                static fn (string $code, string $language): string => '<x lang="' . $language . '">' . $code . '</x>',
            ),
        );
    }

    public function testFenceBlockReturnsNullForPlainLine(): void
    {
        self::assertNull((new MarkdownRenderer())->fenceBlock(['plain text'], 0, null));
    }

    public function testHeadingBlockRendersHashHeading(): void
    {
        self::assertSame(['<h4>Deep</h4>' . "\n", 1], (new MarkdownRenderer())->headingBlock(['### Deep'], 0));
    }

    public function testHeadingBlockRendersHorizontalRule(): void
    {
        self::assertSame(['<hr>' . "\n", 1], (new MarkdownRenderer())->headingBlock(['***'], 0));
    }

    public function testHeadingBlockReturnsNullForPlainLine(): void
    {
        self::assertNull((new MarkdownRenderer())->headingBlock(['plain'], 0));
    }

    public function testQuoteBlockCollectsQuoteLines(): void
    {
        self::assertSame(
            ['<blockquote><p>a b</p>' . "\n" . '</blockquote>' . "\n", 2],
            (new MarkdownRenderer())->quoteBlock(['> a', '> b', 'after'], 0),
        );
    }

    public function testQuoteBlockReturnsNullForPlainLine(): void
    {
        self::assertNull((new MarkdownRenderer())->quoteBlock(['plain'], 0));
    }

    public function testListBlockCollectsNestingAndContinuation(): void
    {
        self::assertSame(
            ['<ul><li>one</li><li>two<ul><li>sub</li></ul></li><li>three cont line</li></ul>' . "\n", 5],
            (new MarkdownRenderer())->listBlock(['- one', '- two', '  - sub', '- three', '  cont line', 'stop'], 0),
        );
    }

    public function testListBlockCollectsOrderedItems(): void
    {
        self::assertSame(
            ['<ol><li>first</li><li>second</li></ol>' . "\n", 2],
            (new MarkdownRenderer())->listBlock(['1. first', '2. second'], 0),
        );
    }

    public function testListBlockReturnsNullForPlainLine(): void
    {
        self::assertNull((new MarkdownRenderer())->listBlock(['plain'], 0));
    }

    public function testListHtmlNestsSecondLevelItems(): void
    {
        self::assertSame(
            '<ul><li>a<ul><li>b</li></ul></li><li>c</li></ul>' . "\n",
            (new MarkdownRenderer())->listHtml([['depth' => 0, 'text' => 'a'], ['depth' => 1, 'text' => 'b'], ['depth' => 0, 'text' => 'c']], false),
        );
    }

    public function testListHtmlRendersOrderedTag(): void
    {
        self::assertSame('<ol><li>x</li></ol>' . "\n", (new MarkdownRenderer())->listHtml([['depth' => 0, 'text' => 'x']], true));
    }

    public function testTableBlockRendersHeaderAndRows(): void
    {
        self::assertSame(
            ['<div class="table-wrap"><table><thead><tr><th>A</th><th>B|C</th></tr></thead><tbody><tr><td>1</td><td>2</td></tr></tbody></table></div>' . "\n", 3],
            (new MarkdownRenderer())->tableBlock(['| A | B\\|C |', '|---|---|', '| 1 | 2 |', '', 'after'], 0),
        );
    }

    public function testTableBlockReturnsNullWithoutSeparatorLine(): void
    {
        self::assertNull((new MarkdownRenderer())->tableBlock(['no table'], 0));
    }

    public function testTableCellsSplitsAndUnescapesPipes(): void
    {
        self::assertSame(['a', 'b|c'], (new MarkdownRenderer())->tableCells('| a | b\\|c |'));
    }

    public function testParagraphBlockJoinsLinesUntilBlank(): void
    {
        self::assertSame(['<p>one two</p>' . "\n", 2], (new MarkdownRenderer())->paragraphBlock(['one', 'two', '', 'next'], 0));
    }

    public function testParagraphBlockStopsAtBlockStart(): void
    {
        self::assertSame(['<p>one</p>' . "\n", 1], (new MarkdownRenderer())->paragraphBlock(['one', '# head'], 0));
    }
}
