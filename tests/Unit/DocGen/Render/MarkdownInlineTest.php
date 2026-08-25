<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\MarkdownInline
 * @uses \PhpAiToolkit\DocGen\Render\HtmlText
 */
#[CoversClass(MarkdownInline::class)]
#[UsesClass(HtmlText::class)]
final class MarkdownInlineTest extends TestCase
{
    public function testRenderProtectsAndEscapesCodeSpans(): void
    {
        self::assertSame('use <code>&lt;tag&gt; &amp; &quot;x&quot;</code> now', (new MarkdownInline())->render('use `<tag> & "x"` now'));
    }

    public function testRenderKeepsMarkdownLiteralInsideCodeSpans(): void
    {
        self::assertSame('<code>**not bold**</code>', (new MarkdownInline())->render('`**not bold**`'));
    }

    public function testRenderBoldsDoubleAsterisksAtWordBoundaries(): void
    {
        self::assertSame('this is <strong>bold</strong> text', (new MarkdownInline())->render('this is **bold** text'));
    }

    public function testRenderLeavesMidWordDoubleAsterisksAlone(): void
    {
        self::assertSame('a**b**c', (new MarkdownInline())->render('a**b**c'));
    }

    public function testRenderLeavesQuotedGlobUnbolded(): void
    {
        self::assertSame('match &#039;&quot;**&quot;&#039; quoted', (new MarkdownInline())->render('match \'"**"\' quoted'));
    }

    public function testRenderEmphasizesSingleAsterisks(): void
    {
        self::assertSame('an <em>emphasis</em> here', (new MarkdownInline())->render('an *emphasis* here'));
    }

    public function testRenderReducesImagesToTheirAltTextWithoutRequestingTheRemoteSource(): void
    {
        self::assertSame(
            '<span class="md-target" title="https://img.shields.io/badge/license-MIT-blue.svg">License: MIT</span> and more',
            (new MarkdownInline())->render('![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg) and more'),
        );
    }

    public function testRenderDropsImagesWithoutAltText(): void
    {
        self::assertSame('', (new MarkdownInline())->render('![](https://img.shields.io/badge/build-passing.svg)'));
    }

    public function testRenderLinksHttpTargets(): void
    {
        self::assertSame('see <a href="https://example.com/a">docs</a> now', (new MarkdownInline())->render('see [docs](https://example.com/a) now'));
    }

    public function testRenderKeepsBalancedParenthesesInsideLinkTargets(): void
    {
        self::assertSame(
            'see <a href="https://en.wikipedia.org/wiki/PHP_(language)">PHP</a> now',
            (new MarkdownInline())->render('see [PHP](https://en.wikipedia.org/wiki/PHP_(language)) now'),
        );
    }

    public function testRenderLinksAnchorTargets(): void
    {
        self::assertSame('see <a href="#usage">usage</a> now', (new MarkdownInline())->render('see [usage](#usage) now'));
    }

    public function testRenderLeavesJavascriptSchemeUnlinked(): void
    {
        self::assertSame(
            'bad <span class="md-target" title="javascript:alert">x</span> end',
            (new MarkdownInline())->render('bad [x](javascript:alert) end'),
        );
    }

    public function testRenderMarksRepositoryRelativeTargetsWithoutLinking(): void
    {
        self::assertSame(
            'see <span class="md-target" title="docs/foo.md">the guide</span> now',
            (new MarkdownInline())->render('see [the guide](docs/foo.md) now'),
        );
    }

    public function testRenderEscapesHtmlText(): void
    {
        self::assertSame('&lt;b&gt;&amp;&quot;&lt;/b&gt;', (new MarkdownInline())->render('<b>&"</b>'));
    }
}
