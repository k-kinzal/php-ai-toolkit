<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
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
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Diff\ClassLikeMerger;
use Toolkit\DocGen\Diff\DiffIndex;
use Toolkit\DocGen\Diff\DiffKey;
use Toolkit\DocGen\Diff\DiffStatus;
use Toolkit\DocGen\Diff\DocumentDiffer;
use Toolkit\DocGen\Diff\FunctionMerger;
use Toolkit\DocGen\Diff\LcsMatcher;
use Toolkit\DocGen\Diff\MemberMerger;
use Toolkit\DocGen\Diff\ParameterMerger;
use Toolkit\DocGen\Diff\ProjectDiffer;
use Toolkit\DocGen\Diff\SymbolFingerprint;
use Toolkit\DocGen\Model\Package\ComposerManifest;
use Toolkit\DocGen\Model\Package\DiscoveredPackage;
use Toolkit\DocGen\Model\Package\PackageGraph;
use Toolkit\DocGen\Model\ProjectModel;
use Toolkit\DocGen\Model\Reference\HierarchyIndex;
use Toolkit\DocGen\Model\Reference\SymbolTable;
use Toolkit\DocGen\Model\Reference\TestCaseIndex;
use Toolkit\DocGen\Model\Reference\UsageIndex;
use Toolkit\DocGen\Model\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Model\Symbol\DocBlock;
use Toolkit\DocGen\Model\Symbol\FileSymbols;
use Toolkit\DocGen\Model\Symbol\FunctionDoc;
use Toolkit\DocGen\Model\Symbol\MethodDoc;
use Toolkit\DocGen\Model\Symbol\ParameterDoc;
use Toolkit\DocGen\Model\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Diff\ProjectDiffer
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Model\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Diff\ClassLikeMerger
 * @uses \Toolkit\DocGen\Model\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Diff\DiffKey
 * @uses \Toolkit\DocGen\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Model\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Model\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Diff\DocumentDiffer
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Model\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Model\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Diff\FunctionMerger
 * @uses \Toolkit\DocGen\Model\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Diff\MemberMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Model\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Model\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Model\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Diff\ParameterMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Model\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Diff\SymbolFingerprint
 * @uses \Toolkit\DocGen\Model\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Model\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Model\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Model\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 */
#[CoversClass(ProjectDiffer::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeMerger::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocumentDiffer::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionMerger::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(MemberMerger::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterMerger::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
#[UsesClass(\Toolkit\Mutation\MutationContractReader::class)]
final class ProjectDifferTest extends TestCase
{
    public function testDiffMergesBothRevisionsIntoOneModelOfTheWholeComparison(): void
    {
        $baseSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(): void {} } class Gone {}', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $headSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(int $times): void {} } class Fresh {}', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $index = new DiffIndex('main', 'HEAD');

        $model = (new ProjectDiffer())->diff(
            new ProjectModel('Demo', '/tmp/base', [$package], new PackageGraph([]), $baseSymbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\gone' => ['Domain']], null, []),
            new ProjectModel('Demo', '/tmp/head', [$package], new PackageGraph([]), $headSymbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\engine' => ['Domain']], null, ['a warning'], [], 'https://example.github.io/demo/pr/7', 'https://github.com/example/demo'),
            $index,
        );

        self::assertSame('/tmp/head', $model->root);
        self::assertSame(['a warning'], $model->warnings);
        self::assertSame('https://example.github.io/demo/pr/7', $model->baseUrl);
        self::assertSame('https://github.com/example/demo', $model->repository);
        self::assertCount(3, $model->classLikes);
        self::assertSame(['Domain'], $model->layerAssignments['demo\gone']);
        self::assertNotNull($model->symbolTable->classLike('Demo\Gone'));
        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->classLike('Demo\Engine')));
        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->classLike('Demo\Fresh')));
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->classLike('Demo\Gone')));
        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->namespaceName('demo/pkg', 'Demo')));
        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->package('demo/pkg')));
    }

    public function testClassLikesKeepsTheHeadOrderAndAppendsWhatTheHeadDropped(): void
    {
        $baseSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Gone {}', 'src/Gone.php'),
            'demo/pkg',
            'src/Gone.php',
            false,
        );
        $headSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Fresh {}', 'src/Fresh.php'),
            'demo/pkg',
            'src/Fresh.php',
            false,
        );
        $index = new DiffIndex('main', 'HEAD');

        $classLikes = (new ProjectDiffer())->classLikes(
            new ProjectModel('Demo', '/tmp/base', [], new PackageGraph([]), $baseSymbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []),
            new ProjectModel('Demo', '/tmp/head', [], new PackageGraph([]), $headSymbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []),
            $index,
        );

        self::assertSame(['Demo\Fresh', 'Demo\Gone'], [$classLikes[0]->fqcn, $classLikes[1]->fqcn]);
    }

    public function testDiffKeepsAnEntryPointWhenTheHeadNarrowsItsVisibility(): void
    {
        $baseSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; /** @visibility public */ class Client {}', 'src/Client.php'),
            'demo/pkg',
            'src/Client.php',
            false,
        );
        $headSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; /** @visibility namespace */ class Client {}', 'src/Client.php'),
            'demo/pkg',
            'src/Client.php',
            false,
        );

        $model = (new ProjectDiffer())->diff(
            new ProjectModel('Demo', '/tmp/base', [], new PackageGraph([]), $baseSymbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true),
            new ProjectModel('Demo', '/tmp/head', [], new PackageGraph([]), $headSymbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true),
            new DiffIndex('main', 'HEAD'),
        );

        self::assertTrue($model->isPublicApiClassLike('Demo\Client'));
        $docBlock = $model->classLikes[0]->docBlock;
        self::assertNotNull($docBlock);
        self::assertTrue($docBlock->isRestricted());
    }

    public function testFunctionsAreMergedByTheirFullyQualifiedName(): void
    {
        $baseSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): string { return $name; } function gone(): void {}', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        );
        $headSymbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name, string $greeting): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        );
        $index = new DiffIndex('main', 'HEAD');

        $functions = (new ProjectDiffer())->functions(
            new ProjectModel('Demo', '/tmp/base', [], new PackageGraph([]), [], $baseSymbols->functions, new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []),
            new ProjectModel('Demo', '/tmp/head', [], new PackageGraph([]), [], $headSymbols->functions, new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []),
            $index,
        );

        self::assertCount(2, $functions);
        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->functionSymbol('Demo\greet')));
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->functionSymbol('Demo\gone')));
    }

    public function testPackagesCarryTheOnesOnlyTheBaseRevisionDocumented(): void
    {
        $kept = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', [], [], [], [], []), false);
        $gone = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/old', 'Old package', [], [], [], [], []), false);

        $packages = (new ProjectDiffer())->packages(
            new ProjectModel('Demo', '/tmp/base', [$kept, $gone], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []),
            new ProjectModel('Demo', '/tmp/head', [$kept], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []),
        );

        self::assertSame(['demo/pkg', 'demo/old'], [$packages[0]->manifest->name, $packages[1]->manifest->name]);
    }

    public function testMarkScopesReportsAScopeAsChangedAsTheSymbolsItHolds(): void
    {
        $symbols = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine {}', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $differ = new ProjectDiffer();
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->classLike('Demo\Engine'), DiffStatus::ADDED);

        $differ->markScopes($symbols->classLikes, [], $index);

        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->namespaceName('demo/pkg', 'Demo')));
        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->package('demo/pkg')));
    }
}
