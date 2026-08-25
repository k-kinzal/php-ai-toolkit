<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Render\AssetPublisher;
use Toolkit\DocGen\Render\Diff\DiffBanner;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\DiffModeControl;
use Toolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Render\Diff\SourceDiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\AllItemsPage;
use Toolkit\DocGen\Render\Page\ClassLikePage;
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Render\Page\Component\ExampleHtml;
use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Render\Page\Component\RelationsHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SignatureHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Render\Page\Component\UsageListHtml;
use Toolkit\DocGen\Render\Page\DocumentPage;
use Toolkit\DocGen\Render\Page\FunctionPage;
use Toolkit\DocGen\Render\Page\IndexPage;
use Toolkit\DocGen\Render\Page\LayerPage;
use Toolkit\DocGen\Render\Page\NamespacePage;
use Toolkit\DocGen\Render\Page\PackagePage;
use Toolkit\DocGen\Render\Page\SidebarScope;
use Toolkit\DocGen\Render\Page\SourcePage;
use Toolkit\DocGen\Render\Page\SymbolIndex;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\Signature\PageSignature;
use Toolkit\DocGen\Render\Signature\SidebarDigest;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Render\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\Page\IndexPage
 * @uses \Toolkit\DocGen\Render\Page\LayerPage
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Render\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(SidebarHtml::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class SidebarHtmlTest extends TestCase
{
    public function testBuildRendersPageSectionsPackageBlockAndNamespaceSiblings(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'demo/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner, $text], [$make], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\core\engine' => ['Domain']], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $scope = new SidebarScope('demo/pkg', 'Demo\Core', 'Demo\Core\Engine', [['id' => 'methods', 'label' => 'Methods']]);

        $html = (new SidebarHtml())->build($services, 'demo/pkg/Demo/Core/class.Engine.html', $scope);

        self::assertStringStartsWith('<div class="sb-head"><a class="sb-site" href="../../../../index.html">Demo Docs</a></div>', $html);
        self::assertStringContainsString('<div class="sb-pkg"><a href="../../../../demo/pkg/index.html">demo/pkg</a></div>', $html);
        self::assertStringContainsString('<div class="sb-title">On this page</div><ul class="sb-list"><li><a href="#methods">Methods</a></li></ul>', $html);
        self::assertStringContainsString('<div class="sb-title">Package</div><ul class="sb-list"><li><a href="../../../../demo/pkg/all-items.html">All items</a></li></ul>', $html);
        self::assertStringContainsString(
            '<div class="sb-kind">Layers</div><ul class="sb-list"><li><a href="../../../../demo/pkg/layer.Domain.html">Domain</a></li></ul></nav>'
            . '<nav class="sb-block"><div class="sb-title"><a href="../../../../demo/pkg/Demo/Core/index.html">In Demo\Core</a></div>',
            $html,
        );
        self::assertStringContainsString('<div class="sb-kind">Namespaces</div><ul class="sb-list"><li><a href="../../../../demo/pkg/Demo/Core/Util/index.html">Util</a></li></ul>', $html);
        self::assertStringContainsString('<li class="is-active"><a class="k-class" href="../../../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></li>', $html);
    }

    public function testBuildFallsBackToPackageListWithoutPackageScope(): void
    {
        $packages = [
            new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false),
            new DiscoveredPackage(new ComposerManifest('/tmp/none', 'acme/lib', 'Acme library', [], [], [], [], []), true),
        ];
        $model = new ProjectModel('Demo Docs', '/tmp/none', $packages, new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SidebarHtml())->build($services, 'index.html', new SidebarScope(null, null, null, []));

        self::assertStringContainsString('<div class="sb-title">Packages</div>', $html);
        self::assertStringNotContainsString('sb-pkg', $html);
        self::assertStringNotContainsString('On this page', $html);
    }

    public function testBuildListsPackageNamespacesWhenScopeHasNoNamespace(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SidebarHtml())->build($services, 'demo/pkg/all-items.html', new SidebarScope('demo/pkg', null, null, []));

        self::assertStringNotContainsString('In Demo', $html);
        self::assertStringContainsString(
            '<nav class="sb-block"><div class="sb-title">Package</div><ul class="sb-list">'
            . '<li><a href="../../demo/pkg/all-items.html">All items</a></li></ul></nav>'
            . '<nav class="sb-block"><div class="sb-title">Namespaces</div><ul class="sb-list">'
            . '<li><a href="../../demo/pkg/Demo/Core/index.html" title="Demo\Core">Demo\Core</a></li></ul></nav>',
            $html,
        );
    }

    public function testPackageListMarksVendorPackages(): void
    {
        $packages = [
            new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false),
            new DiscoveredPackage(new ComposerManifest('/tmp/none', 'acme/lib', 'Acme library', [], [], [], [], []), true),
        ];
        $model = new ProjectModel('Demo Docs', '/tmp/none', $packages, new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SidebarHtml())->packageList($services, 'index.html');

        self::assertSame(
            '<nav class="sb-block"><div class="sb-title">Packages</div><ul class="sb-list">'
            . '<li><a href="demo/pkg/index.html">demo/pkg</a></li>'
            . '<li><a href="acme/lib/index.html">acme/lib</a><span class="sb-note">vendor</span></li>'
            . '</ul></nav>',
            $html,
        );
    }

    public function testPageSectionsRendersAnchorsAndNothingWhenEmpty(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<nav class="sb-block"><div class="sb-title">On this page</div><ul class="sb-list">'
            . '<li><a href="#methods">Methods</a></li><li><a href="#relations">Relations</a></li></ul></nav>',
            (new SidebarHtml())->pageSections($services, new SidebarScope('demo/pkg', null, null, [
                ['id' => 'methods', 'label' => 'Methods'],
                ['id' => 'relations', 'label' => 'Relations'],
            ])),
        );
        self::assertSame('', (new SidebarHtml())->pageSections($services, new SidebarScope('demo/pkg', null, null, [])));
    }

    public function testNamespaceBlockLabelsGlobalNamespaceAndListsChildren(): void
    {
        $engine = new ClassLikeDoc('Engine', 'Engine', '', 'class', 'demo/pkg', 'src/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Util\Text', 'Text', 'Util', 'class', 'demo/pkg', 'src/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $text], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SidebarHtml())->namespaceBlock($services, 'demo/pkg/index.html', new SidebarScope('demo/pkg', '', null, []));

        self::assertStringStartsWith('<nav class="sb-block"><div class="sb-title"><a href="../../demo/pkg/index.html">In global namespace</a></div>', $html);
        self::assertStringContainsString('<div class="sb-kind">Namespaces</div><ul class="sb-list"><li><a href="../../demo/pkg/Util/index.html">Util</a></li></ul>', $html);
        self::assertStringContainsString('<li><a class="k-class" href="../../demo/pkg/class.Engine.html">Engine</a></li>', $html);
        self::assertStringContainsString('</nav>', $html);
    }

    public function testNamespaceListBlockListsEveryNamespaceOfThePackage(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $root = new ClassLikeDoc('Root', 'Root', '', 'class', 'demo/pkg', 'src/Root.php', 3, 9, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $root], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<nav class="sb-block"><div class="sb-title">Namespaces</div><ul class="sb-list">'
            . '<li><a href="../../demo/pkg/index.html" title="global namespace">(global)</a></li>'
            . '<li><a href="../../demo/pkg/Demo/Core/index.html" title="Demo\Core">Demo\Core</a></li>'
            . '</ul></nav>',
            (new SidebarHtml())->namespaceListBlock($services, 'demo/pkg/all-items.html', 'demo/pkg'),
        );
    }

    public function testNamespaceListBlockRendersNothingForAPackageWithoutSymbols(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new SidebarHtml())->namespaceListBlock($services, 'demo/pkg/all-items.html', 'demo/pkg'));
    }

    public function testKindListsGroupsSiblingsByKindAndMarksActiveSymbol(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $devOnly = new ClassLikeDoc('Demo\Core\EngineProbe', 'EngineProbe', 'Demo\Core', 'class', 'demo/pkg', 'tests/EngineProbe.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'demo/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner, $devOnly], [$make], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $scope = new SidebarScope('demo/pkg', 'Demo\Core', 'demo\core\engine', []);

        $html = (new SidebarHtml())->kindLists($services, 'demo/pkg/Demo/Core/index.html', $scope);

        self::assertSame(
            '<div class="sb-kind">Interfaces</div><ul class="sb-list">'
            . '<li><a class="k-interface" href="../../../../demo/pkg/Demo/Core/interface.Runner.html">Runner</a></li></ul>'
            . '<div class="sb-kind">Classes</div><ul class="sb-list">'
            . '<li class="is-active"><a class="k-class" href="../../../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></li></ul>'
            . '<div class="sb-kind">Functions</div><ul class="sb-list">'
            . '<li><a class="k-function" href="../../../../demo/pkg/Demo/Core/function.make.html">make</a></li></ul>',
            $html,
        );
        self::assertStringNotContainsString('EngineProbe', $html);
    }

    public function testPackageBlockOmitsLayersWhenNoneAreAssigned(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<nav class="sb-block"><div class="sb-title">Package</div><ul class="sb-list">'
            . '<li><a href="demo/pkg/all-items.html">All items</a></li></ul></nav>',
            (new SidebarHtml())->packageBlock($services, 'index.html', 'demo/pkg'),
        );
    }

    public function testPackageLayersListsAssignedLayersSortedAndUnique(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Infrastructure', 'Domain'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(['Domain', 'Infrastructure'], (new SidebarHtml())->packageLayers($services, 'demo/pkg'));
        self::assertSame([], (new SidebarHtml())->packageLayers($services, 'other/pkg'));
    }

    public function testLayerBlockMarksEveryLayerWithTheStateOfItsSymbols(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Infrastructure'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->classLike('Demo\Core\Engine'), DiffStatus::ADDED);
        $services = (new SiteRenderer())->services($model, $index);

        $html = (new SidebarHtml())->layerBlock($services, 'index.html', 'demo/pkg');

        self::assertStringContainsString('<div class="sb-kind" data-diff="modified">Layers</div>', $html);
        self::assertStringContainsString('<li data-diff="same"><a href="demo/pkg/layer.Domain.html">Domain</a></li>', $html);
        self::assertStringContainsString('<li data-diff="added"><a href="demo/pkg/layer.Infrastructure.html">Infrastructure</a></li>', $html);
        self::assertSame('', (new SidebarHtml())->layerBlock($services, 'index.html', 'other/pkg'));
    }

    public function testLayerStatusesCombineTheSymbolsOfEachLayer(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Domain'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->classLike('Demo\Core\Engine'), DiffStatus::ADDED);
        $index->mark($index->keys()->classLike('Demo\Core\Runner'), DiffStatus::ADDED);

        self::assertSame(
            ['Domain' => DiffStatus::ADDED],
            (new SidebarHtml())->layerStatuses((new SiteRenderer())->services($model, $index), 'demo/pkg'),
        );
        self::assertSame([], (new SidebarHtml())->layerStatuses((new SiteRenderer())->services($model), 'other/pkg'));
    }

    public function testLastSegmentReturnsTrailingNamespaceSegment(): void
    {
        self::assertSame('Util', (new SidebarHtml())->lastSegment('Demo\Core\Util'));
        self::assertSame('Demo', (new SidebarHtml())->lastSegment('Demo'));
        self::assertSame('', (new SidebarHtml())->lastSegment(''));
    }
}
