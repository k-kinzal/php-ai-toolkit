<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Package\PackageDependency;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\LineDiffer;
use Toolkit\DocGen\Discovery\MarkdownDoc;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ClassLikeKind;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Report\AssetPublisher;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\DiffModeControl;
use Toolkit\DocGen\Report\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Report\Diff\SourceDiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownLinks;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\AllItemsPage;
use Toolkit\DocGen\Report\Page\ClassLikePage;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocTextHtml;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\Page\Component\ExampleHtml;
use Toolkit\DocGen\Report\Page\Component\GraphSvg;
use Toolkit\DocGen\Report\Page\Component\MemberHtml;
use Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Report\Page\Component\RelationsHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SignatureHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\Component\UsageListHtml;
use Toolkit\DocGen\Report\Page\DocumentPage;
use Toolkit\DocGen\Report\Page\FunctionPage;
use Toolkit\DocGen\Report\Page\IndexPage;
use Toolkit\DocGen\Report\Page\LayerPage;
use Toolkit\DocGen\Report\Page\NamespacePage;
use Toolkit\DocGen\Report\Page\PackagePage;
use Toolkit\DocGen\Report\Page\SidebarScope;
use Toolkit\DocGen\Report\Page\SourcePage;
use Toolkit\DocGen\Report\Page\SymbolIndex;
use Toolkit\DocGen\Report\PageChrome;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderedSite;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\RepositoryLink;
use Toolkit\DocGen\Report\SearchIndexBuilder;
use Toolkit\DocGen\Report\Signature\PageSignature;
use Toolkit\DocGen\Report\Signature\SidebarDigest;
use Toolkit\DocGen\Report\SiteRenderer;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Page\PackagePage
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeKind
 * @uses \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Report\Page\DocumentPage
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Report\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Report\Page\FunctionPage
 * @uses \Toolkit\DocGen\Report\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\Page\IndexPage
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 * @uses \Toolkit\DocGen\Report\Page\LayerPage
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Report\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Discovery\MarkdownDoc
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownLinks
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Package\PackageDependency
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\Signature\PageSignature
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Report\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Report\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Report\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\SidebarScope
 * @uses \Toolkit\DocGen\Report\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Report\SiteRenderer
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Page\SourcePage
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Report\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Parse\ParsedProject
 * @uses \Toolkit\DocGen\Analysis\AnalysisOptions
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Action\GenerateDocumentation
 * @uses \Toolkit\DocGen\Action\GenerationRequest
 * @uses \Toolkit\DocGen\Action\GenerationResult
 * @uses \Toolkit\DocGen\Report\RenderedSite
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
#[UsesClass(SourceSelection::class)]
#[UsesClass(SourceFile::class)]
#[UsesClass(SourceSet::class)]
#[UsesClass(SourceDiscovery::class)]
#[UsesClass(ParsedProject::class)]
#[UsesClass(AnalysisOptions::class)]
#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(GenerateDocumentation::class)]
#[UsesClass(GenerationRequest::class)]
#[UsesClass(GenerationResult::class)]
#[UsesClass(RenderedSite::class)]
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
        self::assertStringContainsString('<span class="breadcrumb-current">demo/app</span>', $html);
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
            '<div class="sidebar-title">On this page</div><ul class="sidebar-list">'
            . '<li><a href="#public-api">Public API</a></li>'
            . '<li><a href="#layers">Architecture layers</a></li><li><a href="#namespaces">Namespaces</a></li></ul>',
            $html,
        );
        self::assertLessThan(strpos($html, '<h2 id="namespaces">'), strpos($html, '<h2 id="layers">'));
        self::assertLessThan(strpos($html, '<h2 id="layers">'), strpos($html, 'id="public-api"'));
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

        self::assertStringStartsWith('<section class="readme prose"><h2 id="readme">README<a class="anchor" href="#readme">§</a></h2>', $html);
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
