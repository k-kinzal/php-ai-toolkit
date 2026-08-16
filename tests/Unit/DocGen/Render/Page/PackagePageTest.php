<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerModel;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Analysis\Parse\FunctionBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageDependency;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\DiffModeControl;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownLinks;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
use PhpAiToolkit\DocGen\Render\Page\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\PrivateSurfaceHtml;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackagePage::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerModel::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownLinks::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageDependency::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
final class PackagePageTest extends TestCase
{
    public function testRenderProducesCompleteDocument(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new PackagePage())->render($services, $app, null);

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>demo/app — Demo Docs</title>', $html);
        self::assertStringContainsString('<h1><span class="chip chip-kind k-package">package</span>demo/app</h1>', $html);
        self::assertStringContainsString('<span class="crumb-current">demo/app</span>', $html);
        self::assertStringContainsString('<p class="lede">Demo application</p>', $html);
        self::assertStringNotContainsString('README', $html);
        self::assertStringNotContainsString('href="#namespaces"', $html);
    }

    public function testDescriptionReadsWhatThePackageSaysAboutItself(): void
    {
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);

        self::assertSame('Demo application', (new PackagePage())->description($app));
    }

    public function testDescriptionNamesAPackageThatSaysNothing(): void
    {
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []), false);

        self::assertSame('The demo/app package.', (new PackagePage())->description($app));
    }

    public function testRenderOrdersLayersBeforeNamespaces(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/app', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $layers = new LayerModel([], ['Domain' => []]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), $layers, ['demo\core\engine' => ['Domain']], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new PackagePage())->render($services, $app, null);

        self::assertStringContainsString(
            '<div class="sb-title">On this page</div><ul class="sb-list">'
            . '<li><a href="#layers">Architecture layers</a></li><li><a href="#namespaces">Namespaces</a></li></ul>',
            $html,
        );
        self::assertLessThan(strpos($html, '<h2 id="namespaces">'), strpos($html, '<h2 id="layers">'));
    }

    public function testContentRendersDescriptionDependenciesAndReadme(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], ['demo/lib' => '^1.0'], [], []), false);
        $lib = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/lib', 'Demo library', [], [], [], [], []), false);
        $graph = new PackageGraph([new PackageDependency('demo/app', 'demo/lib', 'require')]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app, $lib], $graph, [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new PackagePage())->content($services, 'demo/app/index.html', $app, "# Hello\n\nIntro text.");

        self::assertStringContainsString('<h1><span class="chip chip-kind k-package">package</span>demo/app</h1>', $html);
        self::assertStringContainsString('<p class="lede">Demo application</p>', $html);
        self::assertStringContainsString('<div class="relation-row"><span class="relation-label">Depends on</span> <a href="../../demo/lib/index.html">demo/lib</a></div>', $html);
        self::assertStringContainsString('<h2 id="readme">README<a class="anchor" href="#readme">§</a></h2>', $html);
        self::assertStringContainsString('<h2>Hello</h2>', $html);
        self::assertStringContainsString('Intro text.', $html);
    }

    public function testReadmeSectionResolvesLinksToRenderedDocuments(): void
    {
        $guide = new MarkdownDoc('demo/app', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$guide]);
        $services = (new SiteRenderer())->services($model);

        $html = (new PackagePage())->readmeSection($services, 'demo/app/index.html', 'demo/app', 'See [the guide](docs/guide.md) and [the tree](tree.yaml).');

        self::assertStringStartsWith('<section class="readme"><h2 id="readme">README<a class="anchor" href="#readme">§</a></h2>', $html);
        self::assertStringContainsString('<a href="../../demo/app/doc/docs/guide.md.html">the guide</a>', $html);
        self::assertStringContainsString('<span class="md-target" title="tree.yaml">the tree</span>', $html);
        self::assertSame('', (new PackagePage())->readmeSection($services, 'demo/app/index.html', 'demo/app', null));
        self::assertSame('', (new PackagePage())->readmeSection($services, 'demo/app/index.html', 'demo/app', ''));
    }

    public function testDependencyRowsRendersInternalExternalAndRequiredBy(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $requires = ['php' => '^8.0', 'ext-json' => '*', 'monolog/monolog' => '^3.0', 'demo/lib' => '^1.0'];
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], $requires, [], []), false);
        $lib = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/lib', 'Demo library', [], [], [], [], []), false);
        $graph = new PackageGraph([
            new PackageDependency('demo/app', 'demo/lib', 'require'),
            new PackageDependency('demo/app', 'demo/lib', 'suggest'),
        ]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app, $lib], $graph, [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $appRows = (new PackagePage())->dependencyRows($services, 'demo/app/index.html', $app);

        self::assertStringContainsString(
            '<div class="relation-row"><span class="relation-label">Depends on</span> <a href="../../demo/lib/index.html">demo/lib</a>, '
            . '<a href="../../demo/lib/index.html">demo/lib</a> <span class="chip chip-sm chip-ghost">suggest</span></div>',
            $appRows,
        );
        self::assertStringContainsString(
            '<div class="relation-row"><span class="relation-label">External dependencies</span> '
            . '<code>monolog/monolog</code> <span class="dep-constraint">^3.0</span></div>',
            $appRows,
        );
        self::assertStringNotContainsString('<code>php</code>', $appRows);
        self::assertStringNotContainsString('ext-json', $appRows);

        $libRows = (new PackagePage())->dependencyRows($services, 'demo/lib/index.html', $lib);

        self::assertStringContainsString(
            '<div class="relation-row"><span class="relation-label">Required by</span> <a href="../../demo/app/index.html">demo/app</a>, '
            . '<a href="../../demo/app/index.html">demo/app</a> <span class="chip chip-sm chip-ghost">suggest</span></div>',
            $libRows,
        );
    }

    public function testExternalDependenciesSkipsPlatformAndDocumentedPackages(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $requires = ['php' => '^8.0', 'ext-json' => '*', 'monolog/monolog' => '^3.0', 'demo/lib' => '^1.0'];
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], $requires, [], []), false);
        $lib = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/lib', 'Demo library', [], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app, $lib], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertSame(
            ['<code>monolog/monolog</code> <span class="dep-constraint">^3.0</span>'],
            (new PackagePage())->externalDependencies($services, $app),
        );
        self::assertSame([], (new PackagePage())->externalDependencies($services, $lib));
    }

    public function testNamespaceOverviewCountsSymbolKinds(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo\Core;

class Engine
{
}

interface Renderer
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Core.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/app', 'src/Demo/Core.php', false);
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new PackagePage())->namespaceOverview($services, 'demo/app/index.html', 'demo/app');

        self::assertStringContainsString('<h2 id="namespaces">Namespaces<a class="anchor" href="#namespaces">§</a></h2>', $html);
        self::assertStringContainsString(
            '<tr><td><a href="../../demo/app/Demo/Core/index.html">Demo\Core</a></td>'
            . '<td class="ns-counts"> <span class="ns-count k-interface">1 interface</span> <span class="ns-count k-class">1 class</span></td></tr>',
            $html,
        );
        self::assertSame('', (new PackagePage())->namespaceOverview($services, 'demo/app/index.html', 'demo/lib'));
    }
    public function testLayerCountsTalliesProductionSymbolsPerLayerSorted(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/app', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/app', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Infrastructure', 'Domain'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertSame(['Domain' => 2, 'Infrastructure' => 1], (new PackagePage())->layerCounts($services, 'demo/app'));
        self::assertSame([], (new PackagePage())->layerCounts($services, 'demo/lib'));
    }

    public function testLayerSectionGraphsLayersAndAllowedDependencies(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/app', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/app', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Domain'], 'demo\core\runner' => ['Shared']];
        $layers = new LayerModel([], ['Domain' => ['Shared', 'Absent']]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), $layers, $assignments, null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new PackagePage())->layerSection($services, 'demo/app/index.html', 'demo/app');

        self::assertStringStartsWith('<section><h2 id="layers">Architecture layers<a class="anchor" href="#layers">§</a></h2>', $html);
        self::assertStringContainsString('Layers and allowed dependencies from <code>deptrac.yaml</code>', $html);
        self::assertStringContainsString('href="../../demo/app/layer.Domain.html"', $html);
        self::assertStringContainsString('Domain (1)', $html);
        self::assertStringContainsString('Shared (1)', $html);
        self::assertStringNotContainsString('Absent', $html);
    }

    public function testLayerSectionRendersNothingWithoutLayersOrAssignments(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/app', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $withoutModel = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\core\engine' => ['Domain']], null, []);
        $withoutAssignments = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), new LayerModel([], ['Domain' => []]), [], null, []);

        self::assertSame('', (new PackagePage())->layerSection((new SiteRenderer())->services($withoutModel), 'demo/app/index.html', 'demo/app'));
        self::assertSame('', (new PackagePage())->layerSection((new SiteRenderer())->services($withoutAssignments), 'demo/app/index.html', 'demo/app'));
    }
}
