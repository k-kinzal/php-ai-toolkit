<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
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
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\DiffModeControl;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\LayerPage;
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
 * @covers \Toolkit\DocGen\Report\Page\LayerPage
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
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
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
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(LayerPage::class)]
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
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(LayerModel::class)]
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
#[UsesClass(UsageIndex::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
final class LayerPageTest extends TestCase
{
    public function testRenderProducesCompleteDocumentWithLayerCrumbAndListing(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, new DocBlock('Engine summary.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $layers = new LayerModel([], ['Domain' => ['Shared']]);
        $assignments = ['demo\core\engine' => ['Domain'], 'demo\core\runner' => ['Shared']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), $layers, $assignments, null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new LayerPage())->render($services, 'demo/pkg', 'Domain');

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>Layer Domain — Demo Docs</title>', $html);
        self::assertStringContainsString(
            '<a href="../../demo/pkg/index.html">demo/pkg</a><span class="breadcrumb-sep">::</span><span class="breadcrumb-current">Layer Domain</span>',
            $html,
        );
        self::assertStringContainsString('<h1><span class="chip chip-layer">layer</span>Domain <span class="count">1</span></h1>', $html);
        self::assertStringContainsString('<p class="section-note">May depend on Shared.</p>', $html);
        self::assertStringContainsString('<a class="item-name k-class" href="../../demo/pkg/Demo/Core/class.Engine.html">Engine</a>', $html);
        self::assertStringNotContainsString('Runner', $html);
        self::assertStringContainsString(
            '<li><a href="#namespaces">Namespaces</a></li><li><a href="#classes">Classes</a></li>',
            $html,
        );
    }

    public function testContentHeadsWithLayerChipCountAndDependencyNote(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $layers = new LayerModel([], ['Domain' => []]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), $layers, ['demo\core\engine' => ['Domain']], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = (new SymbolIndex())->inLayer($services, 'demo/pkg', 'Domain');

        $html = (new LayerPage())->content($services, 'demo/pkg/layer.Domain.html', 'Domain', $rows);

        self::assertStringStartsWith(
            "<div class=\"symbol-head\"><h1><span class=\"chip chip-layer\">layer</span>Domain <span class=\"count\">1</span></h1></div>\n",
            $html,
        );
        self::assertStringContainsString('<p class="section-note">This layer may not depend on any other layer.</p>', $html);
        self::assertLessThan(strpos($html, 'This layer may not depend'), strpos($html, 'id="public-api"'));
        self::assertStringContainsString(
            '<h2 id="namespaces">Namespaces<a class="anchor" href="#namespaces">§</a></h2>'
            . '<div class="table-wrap"><table class="symbol-table">'
            . '<tr><td>Demo\Core</td><td class="ns-counts"> <span class="ns-count k-class">1 class</span></td></tr>',
            $html,
        );
        self::assertLessThan(
            strpos($html, '<section class="items" id="classes">'),
            strpos($html, '<h2 id="namespaces">'),
        );
        self::assertStringContainsString('<section class="items" id="classes"><h2>Classes <span class="count">1</span>', $html);
    }

    public function testDependencyRowNamesAllowedLayersOrStatesIndependence(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $layers = new LayerModel([], ['Domain' => ['Shared', 'Contract'], 'Shared' => []]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), $layers, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            "<p class=\"section-note\">May depend on Shared, Contract.</p>\n",
            (new LayerPage())->dependencyRow($services, 'demo/pkg/layer.Domain.html', 'Domain'),
        );
        self::assertSame(
            "<p class=\"section-note\">This layer may not depend on any other layer.</p>\n",
            (new LayerPage())->dependencyRow($services, 'demo/pkg/layer.Shared.html', 'Shared'),
        );
        self::assertSame(
            "<p class=\"section-note\">This layer may not depend on any other layer.</p>\n",
            (new LayerPage())->dependencyRow($services, 'demo/pkg/layer.Unknown.html', 'Unknown'),
        );
    }

    public function testDependencyRowRendersNothingWithoutALayerModel(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new LayerPage())->dependencyRow($services, 'demo/pkg/layer.Domain.html', 'Domain'));
    }
}
