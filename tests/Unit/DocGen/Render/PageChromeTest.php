<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\DiffModeControl;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\RepositoryLink;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\SocialCard;
use PhpAiToolkit\DocGen\Render\SocialMeta;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
