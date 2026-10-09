<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\DiffModeControl;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\AllItemsPage;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\SidebarScope;
use Toolkit\DocGen\Report\Page\SymbolIndex;
use Toolkit\DocGen\Report\PageChrome;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\RepositoryLink;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\SidebarScope
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
final class AllItemsPageTest extends TestCase
{
    public function testRenderProducesCompleteDocumentWithCrumbsAndSectionAnchors(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, new DocBlock('Engine summary.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner, $text], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new AllItemsPage())->render($services, 'demo/pkg');

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>All items — Demo Docs</title>', $html);
        self::assertStringContainsString(
            '<a href="../../demo/pkg/index.html">demo/pkg</a><span class="breadcrumb-sep">::</span><span class="breadcrumb-current">All items</span>',
            $html,
        );
        self::assertStringContainsString('<div class="symbol-head"><h1>All items <span class="count">3</span></h1></div>', $html);
        self::assertStringContainsString('<div class="sidebar-title">On this page</div>', $html);
        self::assertStringContainsString('<li><a href="#interfaces">Interfaces</a></li>', $html);
        self::assertStringContainsString('<li><a href="#classes">Classes</a></li>', $html);
        self::assertStringContainsString(
            '<div class="sidebar-title">Namespaces</div><ul class="sidebar-list">'
            . '<li><a href="../../demo/pkg/Demo/Core/index.html" title="Demo\Core">Demo\Core</a></li>'
            . '<li><a href="../../demo/pkg/Demo/Core/Util/index.html" title="Demo\Core\Util">Demo\Core\Util</a></li>',
            $html,
        );
        self::assertStringNotContainsString('In Demo', $html);
    }

    public function testContentCountsEverySymbolAndGroupsThemByKind(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, new DocBlock('Engine summary.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'demo/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($engine);
        $table->registerClassLike($text);
        $table->registerFunction($make);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $text], [$make], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = (new SymbolIndex())->inPackage($services, 'demo/pkg');

        $html = (new AllItemsPage())->content($services, 'demo/pkg/all-items.html', $rows);

        self::assertStringStartsWith("<div class=\"symbol-head\"><h1>All items <span class=\"count\">3</span></h1></div>\n", $html);
        self::assertStringContainsString('<section class="items" id="classes"><h2>Classes <span class="count">2</span>', $html);
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-class" href="../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></td>'
            . '<td class="item-ns"><a href="../../demo/pkg/Demo/Core/index.html">Demo\Core</a></td>'
            . '<td class="item-summary">Engine summary.</td></tr>',
            $html,
        );
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-class" href="../../demo/pkg/Demo/Core/Util/class.Text.html">Text</a></td>'
            . '<td class="item-ns"><a href="../../demo/pkg/Demo/Core/Util/index.html">Demo\Core\Util</a></td>',
            $html,
        );
        self::assertStringContainsString('<section class="items" id="functions"><h2>Functions <span class="count">1</span>', $html);
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-function" href="../../demo/pkg/Demo/Core/function.make.html">make</a></td>'
            . '<td class="item-ns"><a href="../../demo/pkg/Demo/Core/index.html">Demo\Core</a></td>',
            $html,
        );
    }

    public function testContentRendersOnlyTheHeadingForAnEmptyPackage(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            "<div class=\"symbol-head\"><h1>All items <span class=\"count\">0</span></h1></div>\n",
            (new AllItemsPage())->content($services, 'demo/pkg/all-items.html', []),
        );
    }
}
