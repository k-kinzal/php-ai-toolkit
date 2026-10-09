<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Compare\DiffIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffLine;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\LcsMatcher;
use Toolkit\DocGen\Compare\LineDiffer;
use Toolkit\DocGen\Discovery\MarkdownDoc;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ClassLikeKind;
use Toolkit\DocGen\Parse\Symbol\ConstantDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Report\AssetPublisher;
use Toolkit\DocGen\Report\Cache\CachedPageWriter;
use Toolkit\DocGen\Report\Cache\PageRecord;
use Toolkit\DocGen\Report\Cache\RenderCache;
use Toolkit\DocGen\Report\Diff\DiffBanner;
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
use Toolkit\DocGen\Report\Page\Component\SymbolDescription;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Report\Page\Component\UsageListHtml;
use Toolkit\DocGen\Report\Page\DocumentPage;
use Toolkit\DocGen\Report\Page\FunctionPage;
use Toolkit\DocGen\Report\Page\IndexPage;
use Toolkit\DocGen\Report\Page\LayerPage;
use Toolkit\DocGen\Report\Page\NamespacePage;
use Toolkit\DocGen\Report\Page\PackagePage;
use Toolkit\DocGen\Report\Page\SidebarScope;
use Toolkit\DocGen\Report\Page\SitePages;
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
use Toolkit\DocGen\Report\Signature\SourceDigestIndex;
use Toolkit\DocGen\Report\Signature\SymbolReferenceScanner;
use Toolkit\DocGen\Report\SiteRenderer;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;
use Toolkit\DocGen\Report\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Report\SiteRenderer
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Cache\CacheStore
 * @uses \Toolkit\DocGen\Report\Cache\CachedPageWriter
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeKind
 * @uses \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Symbol\ConstantDoc
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Report\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffLine
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Report\Page\DocumentPage
 * @uses \Toolkit\DocGen\Report\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Report\Page\FunctionPage
 * @uses \Toolkit\DocGen\Report\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\Page\IndexPage
 * @uses \Toolkit\DocGen\Report\Page\LayerPage
 * @uses \Toolkit\DocGen\Compare\LcsMatcher
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Report\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Discovery\MarkdownDoc
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownLinks
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\Page\PackagePage
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\Cache\PageRecord
 * @uses \Toolkit\DocGen\Report\Signature\PageSignature
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Report\Cache\RenderCache
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Report\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Report\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\SidebarScope
 * @uses \Toolkit\DocGen\Report\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Report\Page\SitePages
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Signature\SourceDigestIndex
 * @uses \Toolkit\DocGen\Report\Page\SourcePage
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolDescription
 * @uses \Toolkit\DocGen\Report\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Report\Signature\SymbolReferenceScanner
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Report\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Report\TypeRenderContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\UsageListHtml
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
#[CoversClass(SiteRenderer::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(CachedPageWriter::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffLine::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
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
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownLinks::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageRecord::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderCache::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SitePages::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolDescription::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolReferenceScanner::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
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
final class SiteRendererTest extends TestCase
{
    public function testRenderPublicApiModeOmitsSupportPagesAndKeepsUnlinkedTypeNames(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-public-render-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Client.php', "<?php\n\nnamespace Demo;\n\nclass Client extends Helper {}\n");
        file_put_contents($dir . '/src/Helper.php', "<?php\n\nnamespace Demo;\n\nclass Helper {}\n");
        $public = new DocBlock('Client API.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['public']);
        $restricted = new DocBlock('Support type.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace']);
        $client = new ClassLikeDoc('Demo\Client', 'Client', 'Demo', 'class', 'demo/pkg', 'src/Client.php', 5, 5, false, false, ['Demo\Helper'], [], [], [], [], [], [], null, $public, [], false);
        $helper = new ClassLikeDoc('Demo\Helper', 'Helper', 'Demo', 'class', 'demo/pkg', 'src/Helper.php', 5, 5, false, false, [], [], [], [], [], [], [], null, $restricted, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($client);
        $table->registerClassLike($helper);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$client, $helper]);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$client, $helper], [], $table, $hierarchy, new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true);
        $out = $dir . '/site';

        (new SiteRenderer())->render($model, $out);

        $index = (string) file_get_contents($out . '/index.html');
        $allItems = (string) file_get_contents($out . '/demo/pkg/all-items.html');
        $clientPage = (string) file_get_contents($out . '/demo/pkg/Demo/class.Client.html');
        $search = (string) file_get_contents($out . '/assets/search-index.js');
        self::assertStringContainsString('Public API documentation', $index);
        self::assertStringContainsString('>Client</a>', $allItems);
        self::assertStringNotContainsString('>Helper</a>', $allItems);
        self::assertStringNotContainsString('class.Helper.html', $clientPage);
        self::assertStringContainsString('<span class="t-ext" title="Demo\\Helper">Helper</span>', $clientPage);
        self::assertStringNotContainsString('<h2 id="relations">', $clientPage);
        self::assertStringContainsString('Client', $search);
        self::assertStringNotContainsString('Helper', $search);
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/class.Helper.html');
        self::assertFileDoesNotExist($out . '/src/src/Helper.php.html');
        self::assertFileExists($out . '/src/src/Client.php.html');
    }

    public function testRenderPackagePagesWritesPackageAndNamespaceIndexes(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-' . uniqid('', true);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\\widget' => ['Domain']], null, []);
        $out = $dir . '/site';
        $renderer = new SiteRenderer();

        self::assertCount(4, $renderer->renderPackagePages($renderer->services($model), $model, $out, new CachedPageWriter()));
        self::assertFileExists($out . '/demo/pkg/index.html');
        self::assertFileExists($out . '/demo/pkg/all-items.html');
        self::assertFileExists($out . '/demo/pkg/layer.Domain.html');
        self::assertFileExists($out . '/demo/pkg/Demo/index.html');
    }

    /**
     * @dataProvider providerPublicApiScopes
     * @param 'package'|'layer'|'namespace' $scope
     * @param list<string> $expected
     */
    #[DataProvider('providerPublicApiScopes')]
    public function testRenderPublicApiTablesMatchEachPageScopeInBothModes(bool $publicApi, string $scope, array $expected): void
    {
        $buildModel = static function (string $root, bool $publicApi): ProjectModel {
            $public = new DocBlock('Published API.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['PUBLIC']);
            $restricted = new DocBlock('Implementation.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace']);
            $classes = [];
            $table = new SymbolTable();
            $assignments = [];
            foreach ([
                ['Client', 'Demo\\Api', 'class', 'demo/pkg', $public, false, 'Domain'],
                ['Contract', 'Demo\\Api', 'interface', 'demo/pkg', $public, false, 'Domain'],
                ['Extension', 'Demo\\Api', 'trait', 'demo/pkg', $public, false, 'Domain'],
                ['Status', 'Demo\\Api', 'enum', 'demo/pkg', $public, false, 'Domain'],
                ['Service', 'Demo\\Other', 'class', 'demo/pkg', $public, false, 'Application'],
                ['Helper', 'Demo\\Internal', 'class', 'demo/pkg', $restricted, false, 'Internal'],
                ['Foreign', 'Other', 'class', 'other/pkg', $public, false, 'Domain'],
                ['ApiTest', 'Demo\\Tests', 'class', 'demo/pkg', $public, true, 'Domain'],
            ] as [$name, $namespace, $kind, $package, $doc, $dev, $layer]) {
                $classLike = new ClassLikeDoc($namespace . '\\' . $name, $name, $namespace, $kind, $package, 'src/' . $name . '.php', 1, 2, false, false, [], [], [], [], [], [], [], null, $doc, [], $dev);
                $classes[] = $classLike;
                $table->registerClassLike($classLike);
                $assignments[strtolower($classLike->fqcn)] = [$layer];
            }
            $functions = [
                new FunctionDoc('Demo\\Api\\connect', 'connect', 'Demo\\Api', 'demo/pkg', 'src/connect.php', 1, 2, [], new TypeSignature('\\Demo\\Internal\\Helper', null), $public, [], false),
                new FunctionDoc('Demo\\Internal\\hidden', 'hidden', 'Demo\\Internal', 'demo/pkg', 'src/hidden.php', 1, 2, [], new TypeSignature(null, null), null, [], false),
            ];
            foreach ($functions as $function) {
                $table->registerFunction($function);
            }
            $packages = [
                new DiscoveredPackage(new ComposerManifest($root, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false),
                new DiscoveredPackage(new ComposerManifest($root, 'other/pkg', 'Other package', ['Other\\' => ['src']], [], [], [], []), false),
            ];

            return new ProjectModel('Demo Docs', $root, $packages, new PackageGraph([]), $classes, $functions, $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, [], [], null, null, $publicApi);
        };
        $model = $buildModel('/tmp/none', $publicApi);
        $services = (new SiteRenderer())->services($model);
        $pages = [
            'package' => (new PackagePage())->render($services, $model->packages[0], null),
            'layer' => (new LayerPage())->render($services, 'demo/pkg', 'Domain'),
            'namespace' => (new NamespacePage())->render($services, 'demo/pkg', 'Demo\\Api'),
        ];
        $html = $pages[$scope];
        self::assertStringContainsString('href="#public-api">Public API</a>', $html);
        preg_match('/<section[^>]*id="public-api">(.*?)<\/section>/s', $html, $section);
        $sectionHtml = $section[1] ?? '';
        self::assertNotSame('', $sectionHtml);
        preg_match_all('/class="item-name [^"]+" href="[^"]+">([^<]+)<\/a>/', $sectionHtml, $names);
        sort($expected);
        $actual = $names[1];
        sort($actual);
        self::assertSame($expected, $actual);
        self::assertStringContainsString('<table', $sectionHtml);
        self::assertStringNotContainsString('Helper', $sectionHtml);
        self::assertStringNotContainsString('Foreign', $sectionHtml);
        self::assertStringNotContainsString('ApiTest', $sectionHtml);
    }

    /**
     * @return iterable<string, array{bool, 'package'|'layer'|'namespace', list<string>}>
     */
    public static function providerPublicApiScopes(): iterable
    {
        foreach ([false, true] as $publicApi) {
            $mode = $publicApi ? 'public' : 'complete';
            yield $mode . ' package' => [$publicApi, 'package', ['Client', 'Contract', 'Extension', 'Status', 'Service', 'connect']];
            yield $mode . ' layer' => [$publicApi, 'layer', ['Client', 'Contract', 'Extension', 'Status']];
            yield $mode . ' namespace' => [$publicApi, 'namespace', ['Client', 'Contract', 'Extension', 'Status', 'connect']];
        }
    }

    public function testRenderSwitchingToPublicApiRemovesPrivatePagesAndReusesTheFilteredSite(): void
    {
        $buildModel = static function (string $root, bool $publicApi): ProjectModel {
            $public = new DocBlock('Published API.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['PUBLIC']);
            $restricted = new DocBlock('Implementation.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace']);
            $classes = [];
            $table = new SymbolTable();
            $assignments = [];
            foreach ([
                ['Client', 'Demo\\Api', 'class', 'demo/pkg', $public, false, 'Domain'],
                ['Contract', 'Demo\\Api', 'interface', 'demo/pkg', $public, false, 'Domain'],
                ['Extension', 'Demo\\Api', 'trait', 'demo/pkg', $public, false, 'Domain'],
                ['Status', 'Demo\\Api', 'enum', 'demo/pkg', $public, false, 'Domain'],
                ['Service', 'Demo\\Other', 'class', 'demo/pkg', $public, false, 'Application'],
                ['Helper', 'Demo\\Internal', 'class', 'demo/pkg', $restricted, false, 'Internal'],
                ['Foreign', 'Other', 'class', 'other/pkg', $public, false, 'Domain'],
                ['ApiTest', 'Demo\\Tests', 'class', 'demo/pkg', $public, true, 'Domain'],
            ] as [$name, $namespace, $kind, $package, $doc, $dev, $layer]) {
                $classLike = new ClassLikeDoc($namespace . '\\' . $name, $name, $namespace, $kind, $package, 'src/' . $name . '.php', 1, 2, false, false, [], [], [], [], [], [], [], null, $doc, [], $dev);
                $classes[] = $classLike;
                $table->registerClassLike($classLike);
                $assignments[strtolower($classLike->fqcn)] = [$layer];
            }
            $functions = [
                new FunctionDoc('Demo\\Api\\connect', 'connect', 'Demo\\Api', 'demo/pkg', 'src/connect.php', 1, 2, [], new TypeSignature('\\Demo\\Internal\\Helper', null), $public, [], false),
                new FunctionDoc('Demo\\Internal\\hidden', 'hidden', 'Demo\\Internal', 'demo/pkg', 'src/hidden.php', 1, 2, [], new TypeSignature(null, null), null, [], false),
            ];
            foreach ($functions as $function) {
                $table->registerFunction($function);
            }
            $packages = [
                new DiscoveredPackage(new ComposerManifest($root, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false),
                new DiscoveredPackage(new ComposerManifest($root, 'other/pkg', 'Other package', ['Other\\' => ['src']], [], [], [], []), false),
            ];

            return new ProjectModel('Demo Docs', $root, $packages, new PackageGraph([]), $classes, $functions, $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, [], [], null, null, $publicApi);
        };
        $dir = sys_get_temp_dir() . '/docgen-public-cache-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        $full = $buildModel($dir, false);
        (static function (ProjectModel $model): void {
            foreach ($model->classLikes as $classLike) {
                file_put_contents($model->root . '/' . $classLike->file, '<?php // ' . $classLike->fqcn);
            }
            foreach ($model->functions as $function) {
                file_put_contents($model->root . '/' . $function->file, '<?php // ' . $function->fqn);
            }
        })($full);
        $renderer = new SiteRenderer();
        $out = $dir . '/site';
        $cache = new RenderCache($dir . '/cache', $out);
        $renderer->render($full, $out, null, 1, $cache);
        self::assertFileExists($out . '/demo/pkg/Demo/Internal/class.Helper.html');
        $public = $buildModel($dir, true);
        $again = new RenderCache($dir . '/cache', $out);
        $again->load();
        $count = $renderer->render($public, $out, null, 1, $again)->pages;

        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/Internal/class.Helper.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/Internal/index.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/Internal/function.hidden.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/layer.Internal.html');
        self::assertFileDoesNotExist($out . '/src/src/Helper.php.html');
        self::assertFileDoesNotExist($out . '/src/src/ApiTest.php.html');
        self::assertFileDoesNotExist($out . '/src/src/hidden.php.html');
        self::assertFileExists($out . '/demo/pkg/Demo/index.html');
        self::assertFileExists($out . '/demo/pkg/Demo/Api/function.connect.html');
        $functionPage = (string) file_get_contents($out . '/demo/pkg/Demo/Api/function.connect.html');
        self::assertStringContainsString('>Helper</span>', $functionPage);
        self::assertStringNotContainsString('class.Helper.html', $functionPage);
        $cached = new RenderCache($dir . '/cache', $out);
        $cached->load();
        self::assertSame($count, $renderer->render($public, $out, null, 1, $cached)->pages);
        self::assertSame($count, $cached->reused());
        self::assertSame(0, $cached->rendered());
        $fresh = $dir . '/fresh';
        self::assertSame($count, $renderer->render($public, $fresh, null, 1)->pages);
        self::assertSame(file_get_contents($fresh . '/index.html'), file_get_contents($out . '/index.html'));
        self::assertSame(file_get_contents($fresh . '/demo/pkg/index.html'), file_get_contents($out . '/demo/pkg/index.html'));
        self::assertSame(file_get_contents($fresh . '/demo/pkg/layer.Domain.html'), file_get_contents($out . '/demo/pkg/layer.Domain.html'));
        self::assertSame(file_get_contents($fresh . '/demo/pkg/Demo/Api/index.html'), file_get_contents($out . '/demo/pkg/Demo/Api/index.html'));
        self::assertSame(file_get_contents($fresh . '/demo/pkg/Demo/Api/function.connect.html'), file_get_contents($out . '/demo/pkg/Demo/Api/function.connect.html'));
        self::assertSame(file_get_contents($fresh . '/assets/search-index.js'), file_get_contents($out . '/assets/search-index.js'));
    }

    public function testRenderDocumentPagesWritesOnePagePerReadableDocument(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-' . uniqid('', true);
        mkdir($dir . '/docs', 0777, true);
        file_put_contents($dir . '/docs/guide.md', "# Guide\n\nHello guide.\n");
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $documents = [
            new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide'),
            new MarkdownDoc('demo/pkg', 'docs/absent.md', 'docs/absent.md', 'Absent'),
        ];
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], $documents);
        $out = $dir . '/site';
        $renderer = new SiteRenderer();

        self::assertCount(1, $renderer->renderDocumentPages($renderer->services($model), $model, $out, new CachedPageWriter()));
        self::assertFileExists($out . '/demo/pkg/doc/docs/guide.md.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/doc/docs/absent.md.html');
        self::assertStringContainsString('Hello guide.', (string) file_get_contents($out . '/demo/pkg/doc/docs/guide.md.html'));
    }

    public function testServicesBuildsRenderKitForModel(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $kit = $renderer->services($model);
        $again = $renderer->services($model);

        self::assertSame($model, $kit->model);
        self::assertNotSame($kit, $again);
        self::assertSame($kit->url, $again->url);
        self::assertNotSame($kit->escaper, $again->escaper);
        self::assertSame('demo/pkg/index.html', $kit->url->packagePage('demo/pkg'));
    }

    public function testRenderSourcePagesReadsEachFileFromTheRevisionThatHasIt(): void
    {
        $head = sys_get_temp_dir() . '/docgen-render-head-' . bin2hex(random_bytes(4));
        $base = sys_get_temp_dir() . '/docgen-render-base-' . bin2hex(random_bytes(4));
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        mkdir($head . '/src', 0777, true);
        mkdir($base . '/src', 0777, true);
        file_put_contents($head . '/src/Kept.php', '<?php');
        file_put_contents($base . '/src/Kept.php', '<?php');
        file_put_contents($base . '/src/Gone.php', '<?php');
        $kept = new ClassLikeDoc('Demo\Kept', 'Kept', 'Demo', 'class', 'demo/pkg', 'src/Kept.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $gone = new ClassLikeDoc('Demo\Gone', 'Gone', 'Demo', 'class', 'demo/pkg', 'src/Gone.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $absent = new ClassLikeDoc('Demo\Absent', 'Absent', 'Demo', 'class', 'demo/pkg', 'src/Absent.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('T', $head, [], new PackageGraph([]), [$kept, $gone, $absent], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->renderSourcePages($renderer->services($model, new DiffIndex('main', 'HEAD', $base)), $model, $out, new CachedPageWriter());

        self::assertCount(2, $records);
        self::assertFileExists($out . '/src/src/Kept.php.html');
        self::assertFileExists($out . '/src/src/Gone.php.html');
        self::assertFileDoesNotExist($out . '/src/src/Absent.php.html');
    }

    public function testRenderClassLikePagesWritesOnePagePerDocumentedSymbol(): void
    {
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        $engine = new ClassLikeDoc('Demo\Engine', 'Engine', 'Demo', 'class', 'demo/pkg', 'src/Engine.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $probe = new ClassLikeDoc('Demo\Probe', 'Probe', 'Demo', 'class', 'demo/pkg', 'tests/Probe.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $model = new ProjectModel('T', '/tmp/none', [], new PackageGraph([]), [$engine, $probe], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->renderClassLikePages($renderer->services($model), $model, $out, new CachedPageWriter(), 2);

        self::assertCount(1, $records);
        self::assertFileExists($out . '/demo/pkg/Demo/class.Engine.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/class.Probe.html');
    }

    public function testWriteSourcePagesSkipsFilesNoRevisionHas(): void
    {
        $root = sys_get_temp_dir() . '/docgen-render-src-' . bin2hex(random_bytes(4));
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Kept.php', '<?php');
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->writeSourcePages($renderer->services($model), $root, $out, new CachedPageWriter(), ['src/Kept.php', 'src/Absent.php']);

        self::assertCount(1, $records);
        self::assertFileExists($out . '/src/src/Kept.php.html');
        self::assertFileDoesNotExist($out . '/src/src/Absent.php.html');
    }

    public function testRenderFunctionPagesWritesOnePagePerDocumentedFunction(): void
    {
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        $greet = new FunctionDoc('Demo\\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $probe = new FunctionDoc('Demo\\probe', 'probe', 'Demo', 'demo/pkg', 'tests/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], true);
        $model = new ProjectModel('T', '/tmp/none', [], new PackageGraph([]), [], [$greet, $probe], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->renderFunctionPages($renderer->services($model), $model, $out, new CachedPageWriter());

        self::assertCount(1, $records);
        self::assertFileExists($out . '/demo/pkg/Demo/function.greet.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/function.probe.html');
    }

    public function testNamespacePagesSkipsTheGlobalNamespace(): void
    {
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        $global = new ClassLikeDoc('Loose', 'Loose', '', 'class', 'demo/pkg', 'src/Loose.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $scoped = new ClassLikeDoc('Demo\\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('T', '/tmp/none', [], new PackageGraph([]), [$global, $scoped], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->namespacePages($renderer->services($model), $model, $out, new CachedPageWriter(), 'demo/pkg');

        self::assertCount(1, $records);
        self::assertSame('demo/pkg/Demo/index.html', $records[0]->path);
    }

    public function testRenderLeavesUnchangedPagesAloneAndRemovesVanishedOnes(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Widget.php', "<?php\n\nnamespace Demo;\n\nfinal class Widget\n{\n}\n");
        $widget = new ClassLikeDoc('Demo\\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $gone = new ClassLikeDoc('Demo\\Gone', 'Gone', 'Demo', 'class', 'demo/pkg', 'src/Gone.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $full = new ProjectModel('Demo Docs', $dir, [$package], new PackageGraph([]), [$widget, $gone], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $smaller = new ProjectModel('Demo Docs', $dir, [$package], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $out = $dir . '/site';
        $cache = new RenderCache($dir . '/cache', $out);
        $cache->load();
        $renderer = new SiteRenderer();

        $renderer->render($full, $out, null, 1, $cache);
        $written = (string) file_get_contents($out . '/src/src/Widget.php.html');
        $again = new RenderCache($dir . '/cache', $out);
        $again->load();
        $renderer->render($smaller, $out, null, 1, $again);

        self::assertSame(1, $again->reused());
        self::assertSame(5, $again->rendered());
        self::assertSame($written, file_get_contents($out . '/src/src/Widget.php.html'));
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/class.Gone.html');
    }
}
