<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report;

use PHPUnit\Framework\Attributes\CoversClass;
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
 * @uses \Toolkit\DocGen\Report\SiteRenderer
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
 * @covers \Toolkit\DocGen\Report\RenderedSite
 */
#[UsesClass(SiteRenderer::class)]
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

#[CoversClass(RenderedSite::class)]
final class RenderedSiteTest extends TestCase
{
    public function testRenderWritesCompleteSite(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Widget.php', "<?php\n\nnamespace Demo;\n\nfinal class Widget\n{\n}\n");
        file_put_contents($dir . '/src/Helper.php', "<?php\n\nnamespace Demo;\n\nfinal class Helper\n{\n}\n");
        file_put_contents($dir . '/README.md', "# Demo\n\nHello readme.\n");
        $summary = new DocBlock('Widget summary.', '', [], null, null, [], [], [], [], [], [], null, false, '/** */');
        $widget = new ClassLikeDoc(
            'Demo\Widget',
            'Widget',
            'Demo',
            'class',
            'demo/pkg',
            'src/Widget.php',
            5,
            7,
            false,
            true,
            [],
            [],
            [],
            [new ConstantDoc('LIMIT', 'public', '10', null, 6)],
            [],
            [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), null, 6, 7)],
            [],
            null,
            $summary,
            [],
            false,
        );
        $helper = new ClassLikeDoc('Demo\Helper', 'Helper', 'Demo', 'class', 'demo/pkg', 'src/Helper.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($helper);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$widget, $helper]);
        $usages = new UsageIndex();
        $usages->build([]);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$widget, $helper], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $out = $dir . '/site';

        self::assertSame(8, (new SiteRenderer())->render($model, $out)->pages);
        self::assertFileExists($out . '/index.html');
        self::assertFileExists($out . '/demo/pkg/index.html');
        self::assertFileExists($out . '/demo/pkg/all-items.html');
        self::assertFileExists($out . '/demo/pkg/Demo/index.html');
        self::assertFileExists($out . '/demo/pkg/Demo/class.Widget.html');
        self::assertFileExists($out . '/demo/pkg/Demo/class.Helper.html');
        self::assertFileExists($out . '/src/src/Widget.php.html');
        self::assertFileExists($out . '/src/src/Helper.php.html');
        self::assertFileExists($out . '/assets/style.css');
        self::assertFileExists($out . '/assets/app.js');
        self::assertFileExists($out . '/assets/search-index.js');
        self::assertFileExists($out . '/.nojekyll');
    }
}
