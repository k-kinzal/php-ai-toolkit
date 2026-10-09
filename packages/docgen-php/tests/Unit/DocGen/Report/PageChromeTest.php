<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\DiffModeControl;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\PageChrome;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\RepositoryLink;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
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
        self::assertStringContainsString('<link rel="stylesheet" href="../../../assets/document-design-v1.2.1.css">', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="../../../assets/style.css">', $html);
        self::assertStringContainsString('<body data-root="../../../">', $html);
        self::assertStringContainsString('<aside class="sidebar" id="sidebar" aria-label="Documentation navigation"><ul>SIDEBAR</ul></aside>', $html);
        self::assertStringContainsString('<nav class="breadcrumbs" aria-label="Breadcrumb"><span>BC</span></nav>', $html);
        self::assertStringContainsString("<main class=\"content\" id=\"content\">\n<p>CONTENT</p></main>", $html);
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
<link rel="stylesheet" href="assets/document-design-v1.2.1.css">
<link rel="stylesheet" href="assets/style.css">
<script>try{var t=localStorage.getItem("docgen-theme");if(t){document.documentElement.dataset.ddTheme=t}}catch(e){}</script>
</head>
<body data-root="">
<a class="skip" href="#content">Skip to content</a><div class="doc">
<aside class="sidebar" id="sidebar" aria-label="Documentation navigation"><ul>SIDEBAR</ul></aside>
<div class="main">
<header class="topbar">
<button class="btn nav-toggle" id="nav-toggle" type="button" aria-controls="sidebar" aria-expanded="false" title="Toggle navigation">☰</button>
<nav class="breadcrumbs" aria-label="Breadcrumb"><span>BC</span></nav>
<div class="topbar-tools">
<input class="input input-search" type="search" id="search" aria-label="Search documentation" placeholder="Search… ( / )" autocomplete="off" spellcheck="false">
<button class="btn" type="button" id="theme-toggle" title="Toggle theme">◐</button>
</div>
</header>
<div class="search-results" id="search-results" hidden></div>
<main class="content" id="content">
<p>CONTENT</p></main>
<footer class="doc-footer">Generated by <a href="https://github.com/k-kinzal/php-ai-toolkit">php-ai-toolkit</a> docgen</footer>
</div></div>
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
            '<script>try{var t=localStorage.getItem("docgen-theme");if(t){document.documentElement.dataset.ddTheme=t}}catch(e){}</script>',
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
