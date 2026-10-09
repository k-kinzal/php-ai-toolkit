<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Compare;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
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
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Compare\ProjectDiffer
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ClassLikeMerger
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Compare\Internal\DocumentDiffer
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Compare\Internal\FunctionMerger
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Compare\LcsMatcher
 * @uses \Toolkit\DocGen\Compare\Internal\MemberMerger
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ParameterMerger
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Compare\Internal\SymbolFingerprint
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
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
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContractReader::class)]
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
