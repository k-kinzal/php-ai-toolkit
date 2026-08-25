<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffSession;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\DiffWorkspace;
use Toolkit\DocGen\Analysis\Diff\DocumentDiffer;
use Toolkit\DocGen\Analysis\Diff\FunctionMerger;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Diff\MemberMerger;
use Toolkit\DocGen\Analysis\Diff\ParameterMerger;
use Toolkit\DocGen\Analysis\Diff\ProjectDiffer;
use Toolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Document\DocumentCollector;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
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
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Analysis\Reference\UsageCollector;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\SourceFileKey;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Git\GitCommandRunner;
use Toolkit\DocGen\Git\GitRepository;
use Toolkit\DocGen\Git\GitWorktree;
use Toolkit\DocGen\Git\RevisionRange;
use Toolkit\DocGen\Git\TempDirectory;
use Toolkit\DocGen\Package\ComposerLockReader;
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

/**
 * @covers \Toolkit\DocGen\Analysis\Diff\DiffWorkspace
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ClassLikeMerger
 * @uses \Toolkit\DocGen\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffSession
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Analysis\Document\DocumentCollector
 * @uses \Toolkit\DocGen\Analysis\Diff\DocumentDiffer
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Diff\FunctionMerger
 * @uses \Toolkit\DocGen\Git\GitCommandRunner
 * @uses \Toolkit\DocGen\Git\GitRepository
 * @uses \Toolkit\DocGen\Git\GitWorktree
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Analysis\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Analysis\Diff\MemberMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ParameterMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Analysis\Diff\ProjectDiffer
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Git\RevisionRange
 * @uses \Toolkit\DocGen\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Diff\SymbolFingerprint
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Git\TempDirectory
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Package\VendorPackageLocator
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
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
#[UsesClass(ProjectAnalyzer::class)]
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
