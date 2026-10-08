<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Coverage\CoverageIndex;
use Toolkit\DocGen\Model\Layer\LayerModel;
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

/**
 * @covers \Toolkit\DocGen\Model\ProjectModel
 * @uses \Toolkit\DocGen\Model\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Model\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Model\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Model\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Model\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Model\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Model\Layer\LayerModel
 * @uses \Toolkit\DocGen\Model\Package\PackageGraph
 * @uses \Toolkit\DocGen\Model\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Model\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Model\Reference\UsageIndex
 */
#[CoversClass(ProjectModel::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(LayerModel::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
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

        $model = new ProjectModel('Demo Docs', '/tmp/demo', [$package], $graph, [$classLike], [], $symbolTable, $hierarchy, $usages, new TestCaseIndex(), null, [], null, ['one warning'], [], 'https://example.github.io/demo', 'https://github.com/example/demo', true);

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
        self::assertTrue($model->publicApi);
        self::assertFalse($model->isPublicApiClassLike('Demo\Greeter'));
        self::assertSame([], $model->publicApiClassLikes());
        self::assertSame([], $model->publicApiFunctions());
    }

    public function testIsPublicApiClassLikeIndexesExplicitAndHistoricalNamesCaseInsensitively(): void
    {
        $docBlock = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '', ['PUBLIC']);
        $classLike = new ClassLikeDoc('Demo\Client', 'Client', 'Demo', 'class', 'demo/app', 'src/Client.php', 1, 5, false, true, [], [], [], [], [], [], [], null, $docBlock, [], false);
        $model = new ProjectModel('Docs', '/tmp/demo', [], new PackageGraph([]), [$classLike], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true, ['Demo\Removed'], ['Demo\removedFunction']);

        self::assertTrue($model->isPublicApiClassLike('demo\CLIENT'));
        self::assertTrue($model->isPublicApiClassLike('DEMO\REMOVED'));
        self::assertTrue($model->isPublicApiFunction('demo\removedfunction'));
        self::assertSame(['demo\removed', 'demo\client'], $model->publicApiClassLikes());
        self::assertSame(['demo\removedfunction'], $model->publicApiFunctions());
    }

    public function testIsPublicApiFunctionIndexesHistoricalNamesCaseInsensitively(): void
    {
        $model = new ProjectModel('Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true, [], ['Demo\create']);

        self::assertTrue($model->isPublicApiFunction('demo\CREATE'));
        self::assertFalse($model->isPublicApiFunction('Demo\inspect'));
    }

    public function testPublicApiClassLikesListsNormalizedNames(): void
    {
        $model = new ProjectModel('Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true, ['Demo\Client']);

        self::assertSame(['demo\client'], $model->publicApiClassLikes());
    }

    public function testPublicApiFunctionsListsNormalizedNames(): void
    {
        $model = new ProjectModel('Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true, [], ['Demo\create']);

        self::assertSame(['demo\create'], $model->publicApiFunctions());
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
        self::assertFalse($model->publicApi);
    }
}
