<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\DocumentDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ProjectDiffer;
use PhpAiToolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
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
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
