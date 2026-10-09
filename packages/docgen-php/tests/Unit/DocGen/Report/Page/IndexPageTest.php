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
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\AllItemsPage;
use Toolkit\DocGen\Report\Page\ClassLikePage;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocTextHtml;
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
 * @covers \Toolkit\DocGen\Report\Page\IndexPage
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeKind
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
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
 * @uses \Toolkit\DocGen\Report\Page\LayerPage
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Report\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Package\PackageDependency
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\Page\PackagePage
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
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Report\Page\SymbolIndex
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
#[CoversClass(IndexPage::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
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
#[UsesClass(LayerPage::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageDependency::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
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
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContractReader::class)]
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
final class IndexPageTest extends TestCase
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

        $html = (new IndexPage())->render($services);

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>Overview — Demo Docs</title>', $html);
        self::assertStringContainsString('<div class="symbol-head"><h1>Demo Docs</h1></div>', $html);
        self::assertStringContainsString('<span class="breadcrumb-current">Overview</span>', $html);
        self::assertStringContainsString('<h2 id="packages">Packages<a class="anchor" href="#packages">§</a></h2>', $html);
    }

    public function testDescriptionPrefersWhatTheProjectSaysAboutItself(): void
    {
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], new SymbolTable(), $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);

        self::assertSame('Demo application', (new IndexPage())->description((new SiteRenderer())->services($model)));
    }

    public function testDescriptionNamesTheSiteWhenNoPackageDescribesItself(): void
    {
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], new SymbolTable(), $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);

        self::assertSame('API documentation of Demo Docs.', (new IndexPage())->description((new SiteRenderer())->services($model)));
    }

    public function testContentRendersWarningsSection(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, ['Something odd happened']);
        $services = (new SiteRenderer())->services($model);

        $html = (new IndexPage())->content($services);

        self::assertStringContainsString(
            '<details class="notice tone-warn"><summary>Analysis warnings <span class="count">1</span></summary>'
            . '<ul><li>Something odd happened</li></ul></details>',
            $html,
        );
    }

    public function testContentLabelsPublicApiModeAndCountsOnlyItsEntryPoints(): void
    {
        $statements = (new AstParser())->parse(<<<'PHP'
<?php

namespace Demo;

/**
 * @visibility public
 */
class Client
{
}

class Helper
{
}
PHP, 'src/Demo/Client.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/app', 'src/Demo/Client.php', false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true);

        $html = (new IndexPage())->content((new SiteRenderer())->services($model));

        self::assertStringContainsString('<strong>Public API documentation</strong>', $html);
        self::assertStringContainsString('<td class="pkg-count">1 symbols</td>', $html);
    }

    public function testContentWritesEveryLineOfTheIndex(): void
    {
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], new SymbolTable(), $hierarchy, $usages, new TestCaseIndex(), null, [], null, ['Something odd happened']);
        $expected = <<<'HTML'
<div class="symbol-head"><h1>Demo Docs</h1></div>
<section><h2 id="packages">Packages<a class="anchor" href="#packages">§</a></h2><div class="table-wrap"><table class="symbol-table"><tr><td><a href="demo/app/index.html">demo/app</a></td><td class="pkg-count">0 symbols</td><td>Demo application</td></tr></table></div></section>
<details class="notice tone-warn"><summary>Analysis warnings <span class="count">1</span></summary><ul><li>Something odd happened</li></ul></details>
HTML;

        self::assertSame($expected . "\n", (new IndexPage())->content((new SiteRenderer())->services($model)));
    }

    public function testPackageTableCountsNonDevSymbols(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/app', 'src/Demo/Widget.php', false);
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $packages = [
            new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false),
            new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/lib', 'Demo library', [], [], [], [], []), true),
        ];
        $model = new ProjectModel('Demo Docs', '/tmp/none', $packages, new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $expected = <<<'HTML'
<section><h2 id="packages">Packages<a class="anchor" href="#packages">§</a></h2><div class="table-wrap"><table class="symbol-table"><tr><td><a href="demo/app/index.html">demo/app</a></td><td class="pkg-count">1 symbols</td><td>Demo application</td></tr><tr><td><a href="demo/lib/index.html">demo/lib</a> <span class="chip chip-sm chip-ghost">vendor</span></td><td class="pkg-count">0 symbols</td><td>Demo library</td></tr></table></div></section>
HTML;

        self::assertSame($expected . "\n", (new IndexPage())->packageTable($services));
    }

    public function testPackageGraphRendersOnlyForMultiplePackages(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $lib = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/lib', 'Demo library', [], [], [], [], []), true);
        $graph = new PackageGraph([new PackageDependency('demo/app', 'demo/lib', 'require')]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app, $lib], $graph, [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new IndexPage())->packageGraph($services);

        self::assertStringContainsString('<h2 id="package-graph">Package Dependencies<a class="anchor" href="#package-graph">§</a></h2>', $html);
        self::assertStringContainsString('node-pkg', $html);
        self::assertStringContainsString('node-vendor', $html);
        self::assertStringContainsString('edge-require', $html);
        self::assertStringContainsString('<span class="legend-item legend-require">require</span>', $html);

        $soloModel = new ProjectModel('Demo Docs', '/tmp/none', [$app], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $soloServices = (new SiteRenderer())->services($soloModel);

        self::assertSame('', (new IndexPage())->packageGraph($soloServices));
    }

    public function testPackageGraphWritesTheSectionAroundTheDrawing(): void
    {
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $app = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/app', 'Demo application', ['Demo\\' => ['src']], [], [], [], []), false);
        $lib = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/lib', 'Demo library', [], [], [], [], []), true);
        $graph = new PackageGraph([new PackageDependency('demo/app', 'demo/lib', 'require')]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$app, $lib], $graph, [], [], new SymbolTable(), $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $expected = <<<'HTML'
<section><h2 id="package-graph">Package Dependencies<a class="anchor" href="#package-graph">§</a></h2><div class="graph-wrap"><svg class="graph" viewBox="0 0 114 176" role="img" style="--dd-draw-width:114px"><path class="edge edge-require" d="M 53.0 42.0 C 53.0 72.0, 53.0 62.0, 53.0 89.0"/><circle class="edge-tip edge-require" cx="53.0" cy="90.0" r="2.6"/><a href="demo/app/index.html"><rect class="node node-pkg" x="8" y="8" width="90" height="34" rx="7"/><text x="53" y="25" text-anchor="middle" dominant-baseline="middle">demo/app</text></a><a href="demo/lib/index.html"><rect class="node node-vendor" x="8" y="92" width="90" height="34" rx="7"/><text x="53" y="109" text-anchor="middle" dominant-baseline="middle">demo/lib</text></a></svg></div><div class="legend graph-legend"><span class="legend-item legend-require">require</span><span class="legend-item legend-require-dev">require-dev</span><span class="legend-item legend-suggest">suggest</span></div></section>
HTML;

        self::assertSame($expected . "\n", (new IndexPage())->packageGraph((new SiteRenderer())->services($model)));
    }
}
