<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\DocumentDiffer;
use Toolkit\DocGen\Analysis\Diff\FunctionMerger;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Diff\MemberMerger;
use Toolkit\DocGen\Analysis\Diff\ParameterMerger;
use Toolkit\DocGen\Analysis\Diff\ProjectDiffer;
use Toolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
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
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;

/**
 * @covers \Toolkit\DocGen\Analysis\Diff\ProjectDiffer
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ClassLikeMerger
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Diff\DocumentDiffer
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\FunctionMerger
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Analysis\Diff\MemberMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ParameterMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Diff\SymbolFingerprint
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
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
