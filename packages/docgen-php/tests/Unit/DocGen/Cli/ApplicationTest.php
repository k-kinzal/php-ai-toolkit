<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationCache;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Action\ProjectAnalysis;
use Toolkit\DocGen\Action\Revision\DiffWorkspace;
use Toolkit\DocGen\Action\Revision\Git\GitCommandRunner;
use Toolkit\DocGen\Action\Revision\Git\GitRepository;
use Toolkit\DocGen\Action\Revision\Git\GitWorktree;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Internal\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Internal\Layer\LayerAssigner;
use Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Cli\Application;
use Toolkit\DocGen\Cli\ClosureOutput;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Cli\DocGenCommand;
use Toolkit\DocGen\Cli\DocGenConfigFactory;
use Toolkit\DocGen\Cli\DocGenConsole;
use Toolkit\DocGen\Cli\DocGenGenerationRunner;
use Toolkit\DocGen\Cli\DocGenHelpText;
use Toolkit\DocGen\Cli\DocGenMemoryLimit;
use Toolkit\DocGen\Cli\DocGenOutputWriter;
use Toolkit\DocGen\Cli\DocGenPreviewServer;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\Internal\ClassLikeMerger;
use Toolkit\DocGen\Compare\Internal\FunctionMerger;
use Toolkit\DocGen\Compare\Internal\ParameterMerger;
use Toolkit\DocGen\Compare\LineDiffer;
use Toolkit\DocGen\Compare\ProjectDiffer;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\DocumentCollector;
use Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader;
use Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver;
use Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery;
use Toolkit\DocGen\Discovery\Internal\Package\VendorPackageLocator;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\Cache\ParseCache;
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap;
use Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner;
use Toolkit\DocGen\Parse\Internal\Reference\UsageCollector;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\ProjectSymbolCollector;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ClassLikeKind;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
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
 * @covers \Toolkit\DocGen\Cli\Application
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Cache\CacheStore
 * @uses \Toolkit\DocGen\Report\Cache\CachedPageWriter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeKind
 * @uses \Toolkit\DocGen\Compare\Internal\ClassLikeMerger
 * @uses \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Analysis\Internal\Layer\DeptracConfigReader
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Report\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Action\Revision\DiffWorkspace
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Cli\ClosureOutput
 * @uses \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Cli\DocGenCommand
 * @uses \Toolkit\DocGen\Cli\DocGenConsole
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\Cli\DocGenConfigFactory
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Cli\DocGenGenerationRunner
 * @uses \Toolkit\DocGen\Cli\DocGenHelpText
 * @uses \Toolkit\DocGen\Cli\DocGenMemoryLimit
 * @uses \Toolkit\DocGen\Cli\DocGenOutputWriter
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Cli\DocGenPreviewServer
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Discovery\Internal\DocumentCollector
 * @uses \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Report\Page\DocumentPage
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Report\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Compare\Internal\FunctionMerger
 * @uses \Toolkit\DocGen\Report\Page\FunctionPage
 * @uses \Toolkit\DocGen\Action\GenerationCache
 * @uses \Toolkit\DocGen\Action\Revision\Git\GitCommandRunner
 * @uses \Toolkit\DocGen\Action\Revision\Git\GitRepository
 * @uses \Toolkit\DocGen\Action\Revision\Git\GitWorktree
 * @uses \Toolkit\DocGen\Report\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\Page\IndexPage
 * @uses \Toolkit\DocGen\Analysis\Internal\Layer\LayerAssigner
 * @uses \Toolkit\DocGen\Report\Page\LayerPage
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Report\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Report\Page\PackagePage
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\Cache\PageRecord
 * @uses \Toolkit\DocGen\Report\Signature\PageSignature
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ParameterMerger
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Cache\ParseCache
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Action\ProjectAnalysis
 * @uses \Toolkit\DocGen\Compare\ProjectDiffer
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Report\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Report\Cache\RenderCache
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Report\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Report\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\SidebarScope
 * @uses \Toolkit\DocGen\Report\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Report\Page\SitePages
 * @uses \Toolkit\DocGen\Report\SiteRenderer
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Signature\SourceDigestIndex
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Report\Page\SourcePage
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
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
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\VendorPackageLocator
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
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
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
#[UsesClass(ClosureOutput::class)]
#[UsesClass(DocGenCliArgumentParser::class)]
#[UsesClass(DocGenCommand::class)]
#[UsesClass(DocGenConsole::class)]
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
#[UsesClass(ProjectAnalysis::class)]
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
#[UsesClass(RepositoryAddress::class)]
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

        self::assertSame(0, $app->run(['docgen', '--help']));
        self::assertStringContainsString('docgen [options]', $output);
        self::assertStringContainsString('--packages=PACKAGES', $output);
        self::assertStringContainsString('-o, --output=OUTPUT', $output);
        self::assertStringContainsString('Exit codes:', $output);

        $output = '';

        self::assertSame(0, $app->run(['docgen', '-V']));
        self::assertSame("docgen 1.0.0\n", $output);
    }

    public function testRunRejectsUnknownOption(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $app = new Application($dir, stderr: static function (string $message) use (&$errors): void {
            $errors .= $message;
        });

        self::assertSame(2, $app->run(['docgen', '--bogus']));
        self::assertSame("DocGen error: The \"--bogus\" option does not exist.\nRun \"docgen --help\" to see every option.\n", $errors);
    }

    public function testRunRejectsUnexpectedArgument(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $app = new Application($dir, stderr: static function (string $message) use (&$errors): void {
            $errors .= $message;
        });

        self::assertSame(2, $app->run(['docgen', 'help']));
        self::assertStringContainsString('DocGen error: No arguments expected, got "help".', $errors);
    }

    public function testRunRejectsMalformedOptionValue(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

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

        self::assertSame(2, $app->run(['docgen', '--jobs=0']));
        self::assertSame('', $output);
        self::assertStringContainsString('DocGen error: Invalid --jobs value: 0.', $errors);
    }

    public function testRunKeepsErrorsWhenQuiet(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-' . uniqid('', true);
        mkdir($dir, 0777, true);

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

        self::assertSame(2, $app->run(['docgen', '--quiet']));
        self::assertSame('', $output);
        self::assertStringContainsString('DocGen error: No composer packages found.', $errors);
    }

    public function testOutputWritesToTheProcessStreamsWithoutInjectedSinks(): void
    {
        self::assertInstanceOf(ConsoleOutput::class, (new Application(sys_get_temp_dir()))->output());
    }

    public function testConsoleRunsTheDocgenCommandOfTheWorkingDirectory(): void
    {
        $console = (new Application(sys_get_temp_dir()))->console();

        self::assertSame('docgen', $console->getName());
        self::assertInstanceOf(DocGenCommand::class, $console->find('docgen'));
    }

    public function testRunGeneratesDocumentationSite(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-cli-<comment>-' . uniqid('', true);
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

        self::assertSame(0, $app->run(['docgen']));
        self::assertStringContainsString('Generated', $output);
        self::assertStringContainsString('docgen-cli-<comment>-', $output);
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

        self::assertSame(2, $app->run(['docgen']));
        self::assertStringContainsString('DocGen error: No composer packages found.', $errors);
    }
}
