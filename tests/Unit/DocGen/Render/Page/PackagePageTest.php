<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ClassLikeKind;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\MarkdownDoc;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageDependency;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Render\AssetPublisher;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\DiffModeControl;
use Toolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Render\Diff\SourceDiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownLinks;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\AllItemsPage;
use Toolkit\DocGen\Render\Page\ClassLikePage;
use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
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
use Toolkit\DocGen\Render\RepositoryLink;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\Signature\PageSignature;
use Toolkit\DocGen\Render\Signature\SidebarDigest;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeKind
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\Page\IndexPage
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 * @uses \Toolkit\DocGen\Render\Page\LayerPage
 * @uses \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Analysis\Model\MarkdownDoc
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownLinks
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageDependency
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\RepositoryLink
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
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
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
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
#[UsesClass(PageSignature::class)]
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
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
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
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
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

    public function testRepositoryLinksAnswerWithTheProjectForItsOwnPackagesAndWithTheManifestForDependencies(): void
    {
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], [], [], [], 'https://github.com/example/app'), false);
        $vendor = new DiscoveredPackage(new ComposerManifest('/tmp/none/vendor/acme/lib', 'acme/lib', 'Acme library', [], [], [], [], [], [], [], 'https://github.com/acme/lib'), true);
        $bare = new DiscoveredPackage(new ComposerManifest('/tmp/none/vendor/acme/bare', 'acme/bare', 'Bare library', [], [], [], [], []), true);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app, $vendor, $bare], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, 'https://github.com/example/project');
        $services = (new SiteRenderer())->services($model);

        self::assertSame(
            ['<a class="repo-link" href="https://github.com/example/project" title="Repository: https://github.com/example/project" rel="noreferrer">github.com/example/project</a>'],
            (new PackagePage())->repositoryLinks($services, $app),
        );
        self::assertSame(
            ['<a class="repo-link" href="https://github.com/acme/lib" title="Repository: https://github.com/acme/lib" rel="noreferrer">github.com/acme/lib</a>'],
            (new PackagePage())->repositoryLinks($services, $vendor),
        );
        self::assertSame([], (new PackagePage())->repositoryLinks($services, $bare));
    }

    public function testRepositoryLinksFallBackToWhatAPackageOfTheProjectDeclaresItself(): void
    {
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], [], [], [], 'https://github.com/example/app'), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertSame(
            ['<a class="repo-link" href="https://github.com/example/app" title="Repository: https://github.com/example/app" rel="noreferrer">github.com/example/app</a>'],
            (new PackagePage())->repositoryLinks($services, $app),
        );
    }

    public function testDependencyRowsNamesTheRepositoryBeforeWhatThePackageDependsOn(): void
    {
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, 'https://github.com/example/project');
        $services = (new SiteRenderer())->services($model);

        self::assertStringStartsWith(
            '<div class="relation-row"><span class="relation-label">Repository</span> '
            . '<a class="repo-link" href="https://github.com/example/project" title="Repository: https://github.com/example/project" rel="noreferrer">github.com/example/project</a></div>',
            (new PackagePage())->dependencyRows($services, 'demo/app/index.html', $app),
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
