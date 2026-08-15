<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageChrome::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
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

        $html = (new PageChrome())->page($kit, 'demo/pkg/Demo/class.Widget.html', 'Widget', '<span>BC</span>', '<ul>SIDEBAR</ul>', '<p>CONTENT</p>');

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

    public function testPageOmitsPrefixForRootPage(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new PageChrome())->page($kit, 'index.html', 'Overview', '', '', '');

        self::assertStringContainsString('<link rel="stylesheet" href="assets/style.css">', $html);
        self::assertStringContainsString('<body data-root="">', $html);
    }
}
