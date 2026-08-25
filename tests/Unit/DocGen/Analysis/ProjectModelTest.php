<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;

/**
 * @covers \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
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
