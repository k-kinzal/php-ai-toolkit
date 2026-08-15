<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SidebarHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
final class SidebarHtmlTest extends TestCase
{
    public function testBuildRendersPageSectionsNamespaceSiblingsAndPackageBlock(): void
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
        self::assertStringContainsString('<div class="sb-title"><a href="../../../../demo/pkg/Demo/Core/index.html">In Demo\Core</a></div>', $html);
        self::assertStringContainsString('<div class="sb-kind">Namespaces</div><ul class="sb-list"><li><a href="../../../../demo/pkg/Demo/Core/Util/index.html">Util</a></li></ul>', $html);
        self::assertStringContainsString('<li class="is-active"><a class="k-class" href="../../../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></li>', $html);
        self::assertStringContainsString('<div class="sb-title">Package</div><ul class="sb-list"><li><a href="../../../../demo/pkg/all-items.html">All items</a></li></ul>', $html);
        self::assertStringContainsString('<div class="sb-kind">Layers</div><ul class="sb-list"><li><a href="../../../../demo/pkg/layer.Domain.html">Domain</a></li></ul>', $html);
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
            '<nav class="sb-block"><div class="sb-title">Namespaces</div><ul class="sb-list">'
            . '<li><a href="../../demo/pkg/Demo/Core/index.html" title="Demo\Core">Demo\Core</a></li></ul></nav>',
            $html,
        );
        self::assertStringContainsString('<div class="sb-title">Package</div>', $html);
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

    public function testLastSegmentReturnsTrailingNamespaceSegment(): void
    {
        self::assertSame('Util', (new SidebarHtml())->lastSegment('Demo\Core\Util'));
        self::assertSame('Demo', (new SidebarHtml())->lastSegment('Demo'));
        self::assertSame('', (new SidebarHtml())->lastSegment(''));
    }
}
