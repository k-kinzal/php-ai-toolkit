<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffLine;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffSession;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffWorkspace;
use PhpAiToolkit\DocGen\Analysis\Diff\DocumentDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ProjectDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector;
use PhpAiToolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
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
use PhpAiToolkit\DocGen\Analysis\Parse\ProjectSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
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
use PhpAiToolkit\DocGen\Cli\DocGenConfigOverrides;
use PhpAiToolkit\DocGen\Cli\DocGenConfigPathResolver;
use PhpAiToolkit\DocGen\Cli\DocGenGenerationRunner;
use PhpAiToolkit\DocGen\Cli\DocGenMemoryLimit;
use PhpAiToolkit\DocGen\Cli\DocGenOutputWriter;
use PhpAiToolkit\DocGen\Cli\DocGenPreviewServer;
use PhpAiToolkit\DocGen\Config\BaseUrl;
use PhpAiToolkit\DocGen\Config\ConfigLoader;
use PhpAiToolkit\DocGen\Config\ConfigScalarReader;
use PhpAiToolkit\DocGen\Config\ConfigStringListReader;
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
use PhpAiToolkit\DocGen\Git\RevisionRange;
use PhpAiToolkit\DocGen\Git\TempDirectory;
use PhpAiToolkit\DocGen\Package\ComposerLockReader;
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
use PhpAiToolkit\DocGen\Render\Page\SymbolDescription;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\Page\TestCaseHtml;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
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
use PhpAiToolkit\DocGen\Render\SocialCard;
use PhpAiToolkit\DocGen\Render\SocialMeta;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenGenerationRunner::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BaseUrl::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(CacheStore::class)]
#[UsesClass(CachedPageWriter::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikeMerger::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerLockReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(CoverageReader::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DeptracConfigReader::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffLine::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffSession::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiffWorkspace::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenConfigOverrides::class)]
#[UsesClass(DocGenConfigPathResolver::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenMemoryLimit::class)]
#[UsesClass(DocGenOutputWriter::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(DocGenPreviewServer::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentCollector::class)]
#[UsesClass(DocumentDiffer::class)]
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
#[UsesClass(LcsMatcher::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MemberMerger::class)]
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
#[UsesClass(RevisionRange::class)]
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
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolReferenceScanner::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TempDirectory::class)]
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
final class DocGenGenerationRunnerTest extends TestCase
{
    public function testRunGeneratesSiteWithZeroConfigDefaults(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
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
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        self::assertSame(0, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false]));
        self::assertStringContainsString('Generated', $output);
        self::assertStringContainsString('build/docs', $output);
        self::assertSame('', $errors);
        self::assertFileExists($dir . '/build/docs/index.html');
    }

    public function testRunAppliesRequestedMemoryLimitAndWarnsAboutUnmatchedVendorGlob(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
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
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        $previous = ini_get('memory_limit');
        $exitCode = $runner->run(['config' => null, 'output' => null, 'vendor' => ['vendor'], 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => null, 'memoryLimit' => DocGenMemoryLimit::FLOOR, 'jobs' => null, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false]);
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame(0, $exitCode);
        self::assertSame(DocGenMemoryLimit::FLOOR, $applied);
        self::assertStringContainsString('Generated', $output);
        self::assertStringContainsString('Warning: Vendor glob "vendor" documented no installed runtime vendor package.', $errors);
    }

    public function testRunReportsMissingExplicitConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            null,
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        self::assertSame(2, $runner->run(['config' => 'missing.yaml', 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false]));
        self::assertStringContainsString('DocGen error: DocGen config not found:', $errors);
        self::assertStringContainsString($dir . '/missing.yaml', $errors);
    }

    public function testRunHonorsConfiguredOutputDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
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
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
output: public/site
YAML);

        $output = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
        ));

        self::assertSame(0, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false]));
        self::assertStringContainsString('public/site', $output);
        self::assertFileExists($dir . '/public/site/index.html');
    }

    public function testRunLaunchesPreviewServerForServeOption(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
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
        $command = '';
        $runner = new DocGenGenerationRunner(
            $dir,
            null,
            null,
            null,
            new DocGenOutputWriter(static function (string $message) use (&$output): void {
                $output .= $message;
            }),
            previewServer: new DocGenPreviewServer(static function (string $launched) use (&$command): int {
                $command = $launched;

                return 0;
            }),
        );

        self::assertSame(0, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => '127.0.0.1:8123', 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false]));
        self::assertStringContainsString('Serving documentation at http://127.0.0.1:8123', $output);
        self::assertStringContainsString(' -S ', $command);
        self::assertStringContainsString('127.0.0.1:8123', $command);
        self::assertStringContainsString($dir . '/build/docs', $command);
    }

    public function testRunReportsMissingComposerPackages(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            null,
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        self::assertSame(2, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false]));
        self::assertStringContainsString('DocGen error: No composer packages found.', $errors);
    }

    public function testLoadConfigBuildsDefaultsWithoutConfigFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $config = (new DocGenGenerationRunner($dir))->loadConfig(null);

        self::assertSame(realpath($dir), $config->root);
        self::assertSame(['.', 'packages/*'], $config->packages);
        self::assertSame([], $config->vendor);
        self::assertSame([], $config->vendorDev);
        self::assertSame([], $config->exclude);
        self::assertSame('build/docs', $config->output);
        self::assertNull($config->title);
        self::assertNull($config->deptrac);
        self::assertNull($config->coverage);
    }

    public function testLoadConfigReadsProjectDocYamlWhenPresent(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
output: public/site
title: Demo Docs
YAML);

        $config = (new DocGenGenerationRunner($dir))->loadConfig(null);

        self::assertSame(realpath($dir), $config->root);
        self::assertSame('public/site', $config->output);
        self::assertSame('Demo Docs', $config->title);
    }

    public function testLoadConfigResolvesExplicitRelativePath(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/conf', 0777, true);
        file_put_contents($dir . '/conf/doc.yaml', <<<'YAML'
title: Custom
YAML);

        $config = (new DocGenGenerationRunner($dir))->loadConfig('conf/doc.yaml');

        self::assertSame(realpath($dir . '/conf'), $config->root);
        self::assertSame('Custom', $config->title);
    }

    public function testLoadConfigRejectsMissingExplicitConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('DocGen config not found: ' . $dir . '/missing.yaml');

        (new DocGenGenerationRunner($dir))->loadConfig('missing.yaml');
    }

    public function testGenerateAnalyzesAndRendersTheProjectAsItIs(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/demo", "autoload": {"psr-4": {"Acme\\\\Demo\\\\": "src/"}}}');
        file_put_contents($dir . '/src/Greeter.php', '<?php namespace Acme\Demo; final class Greeter { public function greet(): string { return "hi"; } }');
        $root = (string) realpath($dir);

        $result = (new DocGenGenerationRunner($dir))->generate(
            new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null),
            $root . '/build/docs',
        );

        self::assertGreaterThan(0, $result['pages']);
        self::assertSame($root, $result['model']->root);
        self::assertFileExists($root . '/build/docs/index.html');
    }

    public function testGenerateDiffRendersTheComparisonAndRemovesTheCheckouts(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/demo", "autoload": {"psr-4": {"Acme\\\\Demo\\\\": "src/"}}}');
        file_put_contents($dir . '/src/Greeter.php', '<?php namespace Acme\Demo; final class Greeter { public function greet(string $name): string { return $name; } }');
        $root = (string) realpath($dir);
        $checkouts = [];
        $temp = new TempDirectory();
        $scratch = $temp->create('docgen-scratch-');
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => 'abc1234'])),
            new GitWorktree(new GitCommandRunner(static function (string $command) use (&$checkouts, $scratch): array {
                preg_match('#\'add\'.*\'([^\']*docgen-diff-[^\']*)\'#', $command, $match);
                $checkout = $match[1] ?? $scratch;
                $checkouts[] = $checkout;
                @mkdir($checkout . '/src', 0777, true);
                file_put_contents($checkout . '/composer.json', '{"name": "acme/demo", "autoload": {"psr-4": {"Acme\\\\Demo\\\\": "src/"}}}');
                file_put_contents($checkout . '/src/Greeter.php', '<?php namespace Acme\Demo; final class Greeter { public function greet(): string { return "hi"; } }');

                return ['status' => 0, 'output' => ''];
            }), $temp),
        );
        $output = '';
        $writer = new DocGenOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });

        $result = (new DocGenGenerationRunner($dir, null, null, null, $writer, null, null, null, null, null, $workspace))->generateDiff(
            new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null),
            $root . '/build/docs',
            new RevisionRange('main'),
        );

        self::assertGreaterThan(0, $result['pages']);
        self::assertStringContainsString('Compared abc1234 to working tree', $output);
        self::assertStringContainsString('data-diff="added"', (string) file_get_contents($root . '/build/docs/acme/demo/Acme/Demo/class.Greeter.html'));
        self::assertNotSame($scratch, $checkouts[0]);
        self::assertDirectoryDoesNotExist($checkouts[0]);

        $temp->remove($scratch);
    }

    public function testReportNamesTheWrittenSiteAndRepeatsEveryWarning(): void
    {
        $output = '';
        $errors = '';
        $writer = new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        );
        $model = new ProjectModel('Demo Docs', '/tmp/project', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, ['first warning', 'second warning']);

        (new DocGenGenerationRunner('/tmp/project', null, null, null, $writer))->report($model, 7, '/tmp/project/build/docs');

        self::assertSame("Generated 7 pages for 0 packages into /tmp/project/build/docs\n", $output);
        self::assertSame("Warning: first warning\nWarning: second warning\n", $errors);
    }

    public function testCachesReadsBackTheCacheOfTheOutputDirectoryItIsGiven(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $config = new DocGenConfig($dir, ['.'], [], [], 'build/docs', null, null, null, [], 'build/doc-gen-cache');
        $runner = new DocGenGenerationRunner($dir);

        $cache = $runner->caches($config, $dir . '/build/docs');

        self::assertInstanceOf(ParseCache::class, $cache->sources);
        self::assertInstanceOf(RenderCache::class, $cache->pages);
        self::assertDirectoryExists($dir . '/build/doc-gen-cache');
    }

    public function testCachesHoldsNothingForARunThatCachesNothing(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $config = new DocGenConfig($dir, ['.'], [], [], 'build/docs', null, null, null, [], null);

        $cache = (new DocGenGenerationRunner($dir))->caches($config, $dir . '/build/docs');

        self::assertNull($cache->sources);
        self::assertNull($cache->pages);
    }

    public function testClearRemovesTheCacheDirectoryOfTheProject(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/build/doc-gen-cache', 0777, true);
        file_put_contents($dir . '/build/doc-gen-cache/entry.cache', '');
        $runner = new DocGenGenerationRunner($dir);

        $runner->clear($dir, null);

        self::assertDirectoryExists($dir . '/build/doc-gen-cache');

        $runner->clear($dir, 'build/doc-gen-cache');

        self::assertDirectoryDoesNotExist($dir . '/build/doc-gen-cache');
    }

    public function testReportCacheStatesWhatWasReusedAndKeepsWhatWasLearned(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $output = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
        ));
        $sources = new ParseCache($dir . '/cache');
        $sources->counted(true);

        $runner->reportCache(new GenerationCache($sources, new RenderCache($dir . '/cache', $dir . '/site')));

        self::assertSame("Cache: 1 of 1 sources and 0 of 0 pages reused\n", $output);
    }

    public function testReportCacheSaysNothingWhenNothingIsCached(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $output = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
        ));

        $runner->reportCache(new GenerationCache());

        self::assertSame('', $output);
    }

    public function testRunLeavesTheSiteAloneWhenNothingChanged(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/demo", "autoload": {"psr-4": {"Acme\\\\Demo\\\\": "src/"}}}');
        file_put_contents($dir . '/src/Greeter.php', "<?php\n\nnamespace Acme\\Demo;\n\nfinal class Greeter\n{\n}\n");
        $output = '';
        $arguments = ['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'baseUrl' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => 1, 'base' => null, 'head' => null, 'cacheDir' => null, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false];
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
        ));

        $runner->run($arguments);
        $output = '';
        $runner->run($arguments);

        self::assertStringContainsString('Cache: 1 of 1 sources and 6 of 6 pages reused', $output);
    }
}
