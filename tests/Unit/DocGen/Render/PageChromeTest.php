<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\DiffModeControl;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\RepositoryLink;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\RepositoryLink
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(PageChrome::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
final class PageChromeTest extends TestCase
{
    public function testPageBuildsDocumentShellWithPrefixedAssets(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new PageChrome())->page($kit, 'demo/pkg/Demo/class.Widget.html', 'Widget', 'The Widget class.', '<span>BC</span>', '<ul>SIDEBAR</ul>', '<p>CONTENT</p>');

        self::assertStringStartsWith("<!DOCTYPE html>\n<html lang=\"en\">\n", $html);
        self::assertStringContainsString('<title>Widget — Demo Docs</title>', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="../../../assets/style.css">', $html);
        self::assertStringContainsString('<body data-root="../../../">', $html);
        self::assertStringContainsString('<nav class="sidebar" id="sidebar"><ul>SIDEBAR</ul></nav>', $html);
        self::assertStringContainsString('<nav class="crumbs"><span>BC</span></nav>', $html);
        self::assertStringContainsString("<main class=\"content\">\n<p>CONTENT</p></main>", $html);
        self::assertStringContainsString('<script src="../../../assets/search-index.js" defer></script>', $html);
        self::assertStringContainsString('<script src="../../../assets/app.js" defer></script>', $html);
    }

    public function testPageWritesEveryLineOfTheDocumentShell(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $expected = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Overview — Demo Docs</title>
<link rel="stylesheet" href="assets/style.css">
<script>try{var t=localStorage.getItem("docgen-theme");if(t){document.documentElement.dataset.theme=t}}catch(e){}</script>
</head>
<body data-root="">
<nav class="sidebar" id="sidebar"><ul>SIDEBAR</ul></nav>
<div class="page">
<header class="topbar">
<button class="nav-toggle" id="nav-toggle" title="Toggle navigation">☰</button>
<nav class="crumbs"><span>BC</span></nav>
<div class="topbar-tools">
<input type="search" id="search" placeholder="Search… ( / )" autocomplete="off" spellcheck="false">
<button id="theme-toggle" title="Toggle theme">◐</button>
</div>
</header>
<div class="search-results" id="search-results" hidden></div>
<main class="content">
<p>CONTENT</p></main>
<footer class="site-footer">Generated by <a href="https://github.com/k-kinzal/php-ai-toolkit">php-ai-toolkit</a> doc-gen</footer>
</div>
<script src="assets/search-index.js" defer></script>
<script src="assets/app.js" defer></script>
</body>
</html>
HTML;

        self::assertSame(
            $expected . "\n",
            (new PageChrome())->page($kit, 'index.html', 'Overview', 'The Demo docs.', '<span>BC</span>', '<ul>SIDEBAR</ul>', '<p>CONTENT</p>'),
        );
    }

    public function testPageCarriesTheWayBackToTheDocumentedRepository(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, 'https://github.com/example/project');
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new PageChrome())->page($kit, 'demo/pkg/Demo/class.Widget.html', 'Widget', '', '', '', '');

        self::assertStringContainsString(
            '<a class="repo-link" href="https://github.com/example/project" title="Repository: https://github.com/example/project" rel="noreferrer">github.com</a>',
            $html,
        );
    }

    public function testPageOmitsPrefixForRootPage(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new PageChrome())->page($kit, 'index.html', 'Overview', '', '', '', '');

        self::assertStringContainsString('<link rel="stylesheet" href="assets/style.css">', $html);
        self::assertStringContainsString('<body data-root="">', $html);
    }

    public function testPageOffersTheDisplayModesOfAComparison(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner(), new DiffHtml(new DiffIndex('main', 'HEAD')));

        $html = (new PageChrome())->page($kit, 'index.html', 'Overview', '', '', '', '');

        self::assertStringContainsString('<div class="diff-modes" id="diff-modes"', $html);
        self::assertStringContainsString('docgen-diff-mode', $html);
        self::assertStringContainsString('<p class="diff-empty" id="diff-empty"', $html);
    }

    public function testBootstrapRestoresTheThemeAndTheDisplayMode(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $plain = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $compared = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner(), new DiffHtml(new DiffIndex('main', 'HEAD')));

        self::assertSame(
            '<script>try{var t=localStorage.getItem("docgen-theme");if(t){document.documentElement.dataset.theme=t}}catch(e){}</script>',
            (new PageChrome())->bootstrap($plain),
        );
        self::assertStringContainsString(
            'document.documentElement.dataset.diffMode=localStorage.getItem("docgen-diff-mode")||"inline"',
            (new PageChrome())->bootstrap($compared),
        );
    }

    public function testEmptyHintCarriesBothAnswersOfAComparedPage(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $plain = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $compared = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner(), new DiffHtml(new DiffIndex('main', 'feature')));

        self::assertSame('', (new PageChrome())->emptyHint($plain));
        self::assertSame(
            '<p class="diff-empty" id="diff-empty" data-changes="Nothing on this page changed between main and feature."'
            . ' data-off="This page documents what main had and feature no longer has. Switch to Diff to read it." hidden></p>' . "\n",
            (new PageChrome())->emptyHint($compared),
        );
    }
}
