<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\DiffWorkspace;
use Toolkit\DocGen\Analysis\Diff\FunctionMerger;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Diff\ParameterMerger;
use Toolkit\DocGen\Analysis\Diff\ProjectDiffer;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Document\DocumentCollector;
use Toolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Layer\LayerAssigner;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ClassLikeKind;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
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
use Toolkit\DocGen\Analysis\Parse\ProjectSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\LocalTypeMap;
use Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageCollector;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\CachedPageWriter;
use Toolkit\DocGen\Cache\CacheStore;
use Toolkit\DocGen\Cache\GenerationCache;
use Toolkit\DocGen\Cache\PageRecord;
use Toolkit\DocGen\Cache\ParseCache;
use Toolkit\DocGen\Cache\RenderCache;
use Toolkit\DocGen\Cache\SourceFileKey;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Cli\Application;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Cli\DocGenConfigFactory;
use Toolkit\DocGen\Cli\DocGenGenerationRunner;
use Toolkit\DocGen\Cli\DocGenHelpText;
use Toolkit\DocGen\Cli\DocGenMemoryLimit;
use Toolkit\DocGen\Cli\DocGenOutputWriter;
use Toolkit\DocGen\Cli\DocGenPreviewServer;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Git\GitCommandRunner;
use Toolkit\DocGen\Git\GitRepository;
use Toolkit\DocGen\Git\GitWorktree;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\ComposerManifestReader;
use Toolkit\DocGen\Package\DevPackageResolver;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageDiscovery;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Package\PackageGraphBuilder;
use Toolkit\DocGen\Package\VendorPackageLocator;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
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
use Toolkit\DocGen\Render\Page\Component\SymbolDescription;
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
use Toolkit\DocGen\Render\RepositoryLink;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\Signature\PageSignature;
use Toolkit\DocGen\Render\Signature\SidebarDigest;
use Toolkit\DocGen\Render\Signature\SourceDigestIndex;
use Toolkit\DocGen\Render\Signature\SymbolReferenceScanner;
use Toolkit\DocGen\Render\SitePages;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Cli\Application
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Cache\CacheStore
 * @uses \Toolkit\DocGen\Cache\CachedPageWriter
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeKind
 * @uses \Toolkit\DocGen\Analysis\Diff\ClassLikeMerger
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Analysis\Layer\DeptracConfigReader
 * @uses \Toolkit\DocGen\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Render\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffWorkspace
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Config\DocGenConfig
 * @uses \Toolkit\DocGen\Cli\DocGenConfigFactory
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Cli\DocGenGenerationRunner
 * @uses \Toolkit\DocGen\Cli\DocGenHelpText
 * @uses \Toolkit\DocGen\Cli\DocGenMemoryLimit
 * @uses \Toolkit\DocGen\Cli\DocGenOutputWriter
 * @uses \Toolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Cli\DocGenPreviewServer
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Document\DocumentCollector
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Diff\FunctionMerger
 * @uses \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Cache\GenerationCache
 * @uses \Toolkit\DocGen\Git\GitCommandRunner
 * @uses \Toolkit\DocGen\Git\GitRepository
 * @uses \Toolkit\DocGen\Git\GitWorktree
 * @uses \Toolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\Page\IndexPage
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerAssigner
 * @uses \Toolkit\DocGen\Render\Page\LayerPage
 * @uses \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Analysis\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Cache\PageRecord
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ParameterMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Cache\ParseCache
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Analysis\Diff\ProjectDiffer
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Cache\RenderCache
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\RepositoryLink
 * @uses \Toolkit\DocGen\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Render\SitePages
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Signature\SourceDigestIndex
 * @uses \Toolkit\DocGen\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolDescription
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Render\Signature\SymbolReferenceScanner
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Render\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Render\TypeRenderContext
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Package\VendorPackageLocator
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
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
