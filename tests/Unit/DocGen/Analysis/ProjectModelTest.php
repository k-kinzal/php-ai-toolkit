<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerModel;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
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

#[CoversClass(ProjectModel::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(LayerModel::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(UsageIndex::class)]
final class ProjectModelTest extends TestCase
{
    public function testStoresAnalyzedProjectData(): void
    {
        $manifest = new ComposerManifest('/tmp/demo', 'demo/app', 'Demo application.', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $graph = new PackageGraph([]);
        $classLike = new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 1, 5, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $symbolTable = new SymbolTable();
        $symbolTable->registerClassLike($classLike);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$classLike]);
        $usages = new UsageIndex();

        $model = new ProjectModel('Demo Docs', '/tmp/demo', [$package], $graph, [$classLike], [], $symbolTable, $hierarchy, $usages, new TestCaseIndex(), null, [], null, ['one warning'], [], 'https://example.github.io/demo', 'https://github.com/example/demo');

        self::assertSame('Demo Docs', $model->title);
        self::assertSame('/tmp/demo', $model->root);
        self::assertSame([$package], $model->packages);
        self::assertSame($graph, $model->graph);
        self::assertSame([$classLike], $model->classLikes);
        self::assertSame([], $model->functions);
        self::assertSame($symbolTable, $model->symbolTable);
        self::assertSame($hierarchy, $model->hierarchy);
        self::assertSame($usages, $model->usages);
        self::assertNull($model->layers);
        self::assertSame([], $model->layerAssignments);
        self::assertNull($model->coverage);
        self::assertSame(['one warning'], $model->warnings);
        self::assertSame('https://example.github.io/demo', $model->baseUrl);
        self::assertSame('https://github.com/example/demo', $model->repository);
    }

    public function testStoresOptionalLayerAndCoverageData(): void
    {
        $layers = new LayerModel([], []);
        $coverage = new CoverageIndex();

        $model = new ProjectModel('Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), $layers, ['demo\greeter' => ['Domain']], $coverage, []);

        self::assertSame($layers, $model->layers);
        self::assertSame(['demo\greeter' => ['Domain']], $model->layerAssignments);
        self::assertSame($coverage, $model->coverage);
        self::assertSame([], $model->warnings);
        self::assertNull($model->baseUrl);
        self::assertNull($model->repository);
    }
}
