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
