<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffSession;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffWorkspace;
use PhpAiToolkit\DocGen\Analysis\Diff\DocumentDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ProjectDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
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
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(RevisionRange::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TempDirectory::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(VendorPackageLocator::class)]
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
        $config = new DocGenConfig('/tmp/project', ['.', 'packages/*'], ['acme/*'], ['tests/*'], 'build/docs', 'Demo Docs', 'deptrac.yaml', 'build/coverage-xml', ['phpunit/*'], 'build/doc-gen-cache', 'https://example.github.io/project', 'https://github.com/example/project');

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
