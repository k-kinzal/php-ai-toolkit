<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Action\Revision;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Action\ProjectAnalysis;
use Toolkit\DocGen\Action\Revision\DiffSession;
use Toolkit\DocGen\Action\Revision\DiffWorkspace;
use Toolkit\DocGen\Action\Revision\Git\GitCommandRunner;
use Toolkit\DocGen\Action\Revision\Git\GitRepository;
use Toolkit\DocGen\Action\Revision\Git\GitWorktree;
use Toolkit\DocGen\Action\Revision\Git\RevisionRange;
use Toolkit\DocGen\Action\Revision\Git\TempDirectory;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Compare\DiffIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\Internal\ClassLikeMerger;
use Toolkit\DocGen\Compare\Internal\DocumentDiffer;
use Toolkit\DocGen\Compare\Internal\FunctionMerger;
use Toolkit\DocGen\Compare\Internal\MemberMerger;
use Toolkit\DocGen\Compare\Internal\ParameterMerger;
use Toolkit\DocGen\Compare\Internal\SymbolFingerprint;
use Toolkit\DocGen\Compare\LcsMatcher;
use Toolkit\DocGen\Compare\ProjectDiffer;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\DocumentCollector;
use Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\Internal\Package\ComposerLockReader;
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
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Report\RenderedSite;

/**
 * @covers \Toolkit\DocGen\Action\Revision\DiffWorkspace
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ClassLikeMerger
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Action\Revision\DiffSession
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Discovery\Internal\DocumentCollector
 * @uses \Toolkit\DocGen\Compare\Internal\DocumentDiffer
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Compare\Internal\FunctionMerger
 * @uses \Toolkit\DocGen\Action\Revision\Git\GitCommandRunner
 * @uses \Toolkit\DocGen\Action\Revision\Git\GitRepository
 * @uses \Toolkit\DocGen\Action\Revision\Git\GitWorktree
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Compare\LcsMatcher
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Compare\Internal\MemberMerger
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ParameterMerger
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Action\ProjectAnalysis
 * @uses \Toolkit\DocGen\Compare\ProjectDiffer
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Action\Revision\Git\RevisionRange
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Compare\Internal\SymbolFingerprint
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Action\Revision\Git\TempDirectory
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
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
#[CoversClass(DiffWorkspace::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeMerger::class)]
#[UsesClass(ComposerLockReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(CoverageReader::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffSession::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(DocumentCollector::class)]
#[UsesClass(DocumentDiffer::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionMerger::class)]
#[UsesClass(GitCommandRunner::class)]
#[UsesClass(GitRepository::class)]
#[UsesClass(GitWorktree::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MemberMerger::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageDiscovery::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackageGraphBuilder::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterMerger::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ProjectAnalysis::class)]
#[UsesClass(ProjectDiffer::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(ProjectSymbolCollector::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(RevisionRange::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SourceFileKey::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TempDirectory::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UsageIndex::class)]
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
final class DiffWorkspaceTest extends TestCase
{
    public function testOpenAnalyzesTheCheckedOutBaseAgainstTheWorkingTree(): void
    {
        $project = sys_get_temp_dir() . '/docgen-workspace-' . bin2hex(random_bytes(4));
        mkdir($project . '/src', 0777, true);
        file_put_contents($project . '/composer.json', '{"name": "demo/app", "autoload": {"psr-4": {"Demo\\\\": "src/"}}}');
        file_put_contents($project . '/src/Engine.php', '<?php namespace Demo; class Engine { public function run(int $times): void {} }');
        $temp = new TempDirectory();
        $scratch = $temp->create('docgen-scratch-');
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => 'abc1234'])),
            new GitWorktree(new GitCommandRunner(static function (string $command) use ($scratch): array {
                preg_match('#\'add\'.*\'([^\']*docgen-diff-[^\']*)\'#', $command, $match);
                $checkout = $match[1] ?? $scratch;
                @mkdir($checkout . '/src', 0777, true);
                file_put_contents($checkout . '/composer.json', '{"name": "demo/app", "autoload": {"psr-4": {"Demo\\\\": "src/"}}}');
                file_put_contents($checkout . '/src/Engine.php', '<?php namespace Demo; class Engine { public function run(): void {} }');

                return ['status' => 0, 'output' => ''];
            }), $temp),
        );

        $session = $workspace->open(
            new DocGenConfig((string) realpath($project), ['.'], [], [], 'build/docs', null, null, null),
            new RevisionRange('main'),
        );

        self::assertSame('abc1234', $session->diff->baseLabel());
        self::assertSame(DiffWorkspace::WORKING_TREE, $session->diff->headLabel());
        self::assertNull($session->headPath);
        self::assertNotNull($session->basePath);
        self::assertSame((string) realpath($project), $session->model->root);
        self::assertSame(DiffStatus::MODIFIED, $session->diff->status($session->diff->keys()->classLike('Demo\Engine')));
        self::assertSame(
            DiffStatus::ADDED,
            $session->diff->status($session->diff->keys()->parameter($session->diff->keys()->member('Demo\Engine', DiffKey::METHOD, 'run'), 'times')),
        );

        $workspace->close($session);
    }

    public function testOpenExplainsThatDiffModeNeedsAGitWorkingTree(): void
    {
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 128, 'output' => 'fatal: not a git repository'])),
        );

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Not a git working tree');

        $workspace->open(new DocGenConfig('/tmp/plain', ['.'], [], [], 'build/docs', null, null, null), new RevisionRange('main'));
    }

    public function testOpenRemovesTheCheckoutWhenTheAnalysisFails(): void
    {
        $temp = new TempDirectory();
        $checkouts = [];
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => 'abc1234'])),
            new GitWorktree(new GitCommandRunner(static function (string $command) use (&$checkouts): array {
                preg_match('#\'([^\']*docgen-diff-[^\']*)\'#', $command, $match);
                $checkouts[] = $match[1] ?? '';

                return ['status' => 0, 'output' => ''];
            }), $temp),
        );

        $this->expectException(DocGenException::class);

        $workspace->open(new DocGenConfig('/tmp/docgen-missing-project', ['.'], [], [], 'build/docs', null, null, null), new RevisionRange('main'));
    }

    public function testCloseRemovesEveryCheckoutOfTheSession(): void
    {
        $temp = new TempDirectory();
        $basePath = $temp->create('docgen-diff-');
        $headPath = $temp->create('docgen-diff-');
        $workspace = new DiffWorkspace(
            null,
            new GitWorktree(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => '']), $temp),
        );
        $model = new ProjectModel('Demo', '/tmp/head', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        $workspace->close(new DiffSession($model, new DiffIndex('main', 'HEAD'), '/tmp/repo', $basePath, $headPath));

        self::assertDirectoryDoesNotExist($basePath);
        self::assertDirectoryDoesNotExist($headPath);
    }

    public function testCheckoutResolvesTheRevisionAndLinksTheDependencies(): void
    {
        $temp = new TempDirectory();
        $repository = $temp->create('docgen-repo-');
        mkdir($repository . '/vendor', 0700, true);
        file_put_contents($repository . '/vendor/autoload.php', '<?php');
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => 'abc1234'])),
            new GitWorktree(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => '']), $temp),
        );

        $path = $workspace->checkout($repository, 'main');

        self::assertDirectoryExists($path);
        self::assertFileExists($path . '/vendor/autoload.php');

        $temp->remove($path . '/vendor');
        $temp->remove($path);
        $temp->remove($repository);
    }

    public function testRemoveAllSkipsTheCheckoutsThatWereNeverMade(): void
    {
        $temp = new TempDirectory();
        $basePath = $temp->create('docgen-diff-');
        $workspace = new DiffWorkspace(
            null,
            new GitWorktree(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => '']), $temp),
        );

        $workspace->removeAll('/tmp/repo', $basePath, null);

        self::assertDirectoryDoesNotExist($basePath);
    }

    public function testConfigForKeepsTheDocumentedScopeAndMovesOnlyTheRoot(): void
    {
        $config = new DocGenConfig('/tmp/project', ['.', 'packages/*'], ['acme/*'], ['tests/*'], 'build/docs', 'Demo Docs', 'deptrac.yaml', 'build/coverage-xml', ['phpunit/*'], 'build/docgen-cache', 'https://example.github.io/project', 'https://github.com/example/project');

        $moved = (new DiffWorkspace())->configFor($config, '/tmp/checkout', null);

        self::assertSame('/tmp/checkout', $moved->root);
        self::assertSame(['.', 'packages/*'], $moved->packages);
        self::assertSame(['acme/*'], $moved->vendor);
        self::assertSame(['tests/*'], $moved->exclude);
        self::assertSame('build/docs', $moved->output);
        self::assertSame('Demo Docs', $moved->title);
        self::assertSame('deptrac.yaml', $moved->deptrac);
        self::assertSame(['phpunit/*'], $moved->vendorDev);
        self::assertNull($moved->coverage);
        self::assertSame('https://example.github.io/project', $moved->baseUrl);
        self::assertSame('https://github.com/example/project', $moved->repository);
    }

    public function testCoverageIsResolvedAgainstTheWorkingTreeOfTheProject(): void
    {
        $workspace = new DiffWorkspace();

        self::assertSame(
            '/tmp/project/build/coverage-xml',
            $workspace->coverage(new DocGenConfig('/tmp/project', ['.'], [], [], 'build/docs', null, null, 'build/coverage-xml')),
        );
        self::assertNull($workspace->coverage(new DocGenConfig('/tmp/project', ['.'], [], [], 'build/docs', null, null, null)));
    }

    public function testLabelAsksTheRepositoryForTheShortName(): void
    {
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => '2f0c1a2'])),
        );

        self::assertSame('2f0c1a2', $workspace->label('/tmp/repo', 'main'));
    }

    public function testHeadLabelNamesTheWorkingTreeWhenNoRevisionIsCompared(): void
    {
        $workspace = new DiffWorkspace(
            new GitRepository(new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => '2f0c1a2'])),
        );

        self::assertSame(DiffWorkspace::WORKING_TREE, $workspace->headLabel('/tmp/repo', null));
        self::assertSame('2f0c1a2', $workspace->headLabel('/tmp/repo', 'feature'));
    }
}
