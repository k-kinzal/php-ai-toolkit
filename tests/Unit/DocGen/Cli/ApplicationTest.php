<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffWorkspace;
use PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ProjectDiffer;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector;
use PhpAiToolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\ProjectSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PhpAiToolkit\DocGen\Analysis\ProjectAnalyzer;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\LocalTypeMap;
use PhpAiToolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Cache\CachedPageWriter;
use PhpAiToolkit\DocGen\Cache\CacheStore;
use PhpAiToolkit\DocGen\Cache\GenerationCache;
use PhpAiToolkit\DocGen\Cache\PageRecord;
use PhpAiToolkit\DocGen\Cache\ParseCache;
use PhpAiToolkit\DocGen\Cache\RenderCache;
use PhpAiToolkit\DocGen\Cache\SourceFileKey;
use PhpAiToolkit\DocGen\Cache\ToolkitFingerprint;
use PhpAiToolkit\DocGen\Cli\Application;
use PhpAiToolkit\DocGen\Cli\DocGenCliArgumentParser;
use PhpAiToolkit\DocGen\Cli\DocGenConfigFactory;
use PhpAiToolkit\DocGen\Cli\DocGenGenerationRunner;
use PhpAiToolkit\DocGen\Cli\DocGenHelpText;
use PhpAiToolkit\DocGen\Cli\DocGenMemoryLimit;
use PhpAiToolkit\DocGen\Cli\DocGenOutputWriter;
use PhpAiToolkit\DocGen\Cli\DocGenPreviewServer;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\Config\RepositoryUrl;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PhpAiToolkit\DocGen\Git\GitCommandRunner;
use PhpAiToolkit\DocGen\Git\GitRepository;
use PhpAiToolkit\DocGen\Git\GitWorktree;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\ComposerManifestReader;
use PhpAiToolkit\DocGen\Package\DevPackageResolver;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageDiscovery;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Package\PackageGraphBuilder;
use PhpAiToolkit\DocGen\Package\VendorPackageLocator;
use PhpAiToolkit\DocGen\Parallel\CpuCoreCounter;
use PhpAiToolkit\DocGen\Parallel\WorkerCount;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\Diff\DiffBanner;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\DiffModeControl;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\Component\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SymbolDescription;
use PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SymbolRow;
use PhpAiToolkit\DocGen\Render\Page\Component\TestCaseHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\UsageListHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\RepositoryLink;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;
use PhpAiToolkit\DocGen\Render\Signature\SidebarDigest;
use PhpAiToolkit\DocGen\Render\Signature\SourceDigestIndex;
use PhpAiToolkit\DocGen\Render\Signature\SymbolReferenceScanner;
use PhpAiToolkit\DocGen\Render\SitePages;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\Social\SocialCard;
use PhpAiToolkit\DocGen\Render\Social\SocialMeta;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Cli\Application
 * @uses \PhpAiToolkit\DocGen\Render\Page\AllItemsPage
 * @uses \PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \PhpAiToolkit\DocGen\Render\AssetPublisher
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\AstParser
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \PhpAiToolkit\DocGen\Cache\CacheStore
 * @uses \PhpAiToolkit\DocGen\Cache\CachedPageWriter
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger
 * @uses \PhpAiToolkit\DocGen\Render\Page\ClassLikePage
 * @uses \PhpAiToolkit\DocGen\Package\ComposerManifest
 * @uses \PhpAiToolkit\DocGen\Package\ComposerManifestReader
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader
 * @uses \PhpAiToolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \PhpAiToolkit\DocGen\Analysis\Layer\DeptracConfigReader
 * @uses \PhpAiToolkit\DocGen\Package\DevPackageResolver
 * @uses \PhpAiToolkit\DocGen\Render\Diff\DiffBanner
 * @uses \PhpAiToolkit\DocGen\Render\Diff\DiffHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \PhpAiToolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffWorkspace
 * @uses \PhpAiToolkit\DocGen\Package\DiscoveredPackage
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \PhpAiToolkit\DocGen\Config\DocGenConfig
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenConfigFactory
 * @uses \PhpAiToolkit\DocGen\DocGenException
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenGenerationRunner
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenHelpText
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenMemoryLimit
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenOutputWriter
 * @uses \PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \PhpAiToolkit\DocGen\Cli\DocGenPreviewServer
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\DocumentPage
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger
 * @uses \PhpAiToolkit\DocGen\Render\Page\FunctionPage
 * @uses \PhpAiToolkit\DocGen\Cache\GenerationCache
 * @uses \PhpAiToolkit\DocGen\Git\GitCommandRunner
 * @uses \PhpAiToolkit\DocGen\Git\GitRepository
 * @uses \PhpAiToolkit\DocGen\Git\GitWorktree
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \PhpAiToolkit\DocGen\Render\HtmlText
 * @uses \PhpAiToolkit\DocGen\Render\Page\IndexPage
 * @uses \PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner
 * @uses \PhpAiToolkit\DocGen\Render\Page\LayerPage
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\LocalTypeMap
 * @uses \PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder
 * @uses \PhpAiToolkit\DocGen\Render\MarkdownInline
 * @uses \PhpAiToolkit\DocGen\Render\MarkdownRenderer
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \PhpAiToolkit\DocGen\Render\Page\NamespacePage
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \PhpAiToolkit\DocGen\Package\PackageDiscovery
 * @uses \PhpAiToolkit\DocGen\Package\PackageGraph
 * @uses \PhpAiToolkit\DocGen\Package\PackageGraphBuilder
 * @uses \PhpAiToolkit\DocGen\Render\Page\PackagePage
 * @uses \PhpAiToolkit\DocGen\Render\PageChrome
 * @uses \PhpAiToolkit\DocGen\Cache\PageRecord
 * @uses \PhpAiToolkit\DocGen\Render\Signature\PageSignature
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \PhpAiToolkit\DocGen\Cache\ParseCache
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \PhpAiToolkit\DocGen\Render\PhpHighlighter
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\ProjectDiffer
 * @uses \PhpAiToolkit\DocGen\Analysis\ProjectModel
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ProjectSymbolCollector
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\PropertyTypeScanner
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \PhpAiToolkit\DocGen\Cache\RenderCache
 * @uses \PhpAiToolkit\DocGen\Render\RenderKit
 * @uses \PhpAiToolkit\DocGen\Render\RepositoryLink
 * @uses \PhpAiToolkit\DocGen\Config\RepositoryUrl
 * @uses \PhpAiToolkit\DocGen\Render\SearchIndexBuilder
 * @uses \PhpAiToolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\SidebarScope
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \PhpAiToolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \PhpAiToolkit\DocGen\Render\SitePages
 * @uses \PhpAiToolkit\DocGen\Render\SiteRenderer
 * @uses \PhpAiToolkit\DocGen\Render\SiteUrl
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialCard
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialMeta
 * @uses \PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \PhpAiToolkit\DocGen\Render\Signature\SourceDigestIndex
 * @uses \PhpAiToolkit\DocGen\Filesystem\SourceFileFinder
 * @uses \PhpAiToolkit\DocGen\Cache\SourceFileKey
 * @uses \PhpAiToolkit\DocGen\Render\Page\SourcePage
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SymbolDescription
 * @uses \PhpAiToolkit\DocGen\Render\Page\SymbolIndex
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \PhpAiToolkit\DocGen\Render\Signature\SymbolReferenceScanner
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\TestCaseHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \PhpAiToolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \PhpAiToolkit\DocGen\Render\TypeHtml
 * @uses \PhpAiToolkit\DocGen\Render\TypeRenderContext
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \PhpAiToolkit\DocGen\Package\VendorPackageLocator
 * @uses \PhpAiToolkit\DocGen\Parallel\WorkScheduler
 * @uses \PhpAiToolkit\DocGen\Parallel\WorkerCount
 * @uses \PhpAiToolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(Application::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(CachedPageWriter::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikeMerger::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(CoverageReader::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DeptracConfigReader::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiffWorkspace::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenCliArgumentParser::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenConfigFactory::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenGenerationRunner::class)]
#[UsesClass(DocGenHelpText::class)]
#[UsesClass(DocGenMemoryLimit::class)]
#[UsesClass(DocGenOutputWriter::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(DocGenPreviewServer::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentCollector::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionMerger::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GenerationCache::class)]
#[UsesClass(GitCommandRunner::class)]
#[UsesClass(GitRepository::class)]
#[UsesClass(GitWorktree::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerAssigner::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageDiscovery::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackageGraphBuilder::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageRecord::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterMerger::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(ParseCache::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(ProjectDiffer::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(ProjectSymbolCollector::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderCache::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SitePages::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SourceFileKey::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
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
#[UsesClass(UsageCollector::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(VendorPackageLocator::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class ApplicationTest extends TestCase
{
    public function testRunPrintsHelpAndVersion(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['doc-gen', '--help']));
        self::assertStringContainsString('Usage: doc-gen', $output);

        $output = '';

        self::assertSame(0, $app->run(['doc-gen', '-V']));
        self::assertStringContainsString('doc-gen 1.0.0', $output);
    }

    public function testRunRejectsUnknownOption(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $app = new Application($dir, stderr: static function (string $message) use (&$errors): void {
            $errors .= $message;
        });

        self::assertSame(2, $app->run(['doc-gen', '--bogus']));
        self::assertStringContainsString('Unknown option: --bogus', $errors);
    }

    public function testRunGeneratesDocumentationSite(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/demo",
    "autoload": {"psr-4": {"Acme\\Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Acme\Demo;

final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }
}
PHP);

        $output = '';
        $errors = '';
        $app = new Application(
            $dir,
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        );

        self::assertSame(0, $app->run(['doc-gen']));
        self::assertStringContainsString('Generated', $output);
        self::assertSame('', $errors);
        self::assertFileExists($dir . '/build/docs/index.html');
    }

    public function testRunReportsGenerationErrorsOnStderr(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $app = new Application($dir, stderr: static function (string $message) use (&$errors): void {
            $errors .= $message;
        });

        self::assertSame(2, $app->run(['doc-gen']));
        self::assertStringContainsString('DocGen error: No composer packages found.', $errors);
    }
}
