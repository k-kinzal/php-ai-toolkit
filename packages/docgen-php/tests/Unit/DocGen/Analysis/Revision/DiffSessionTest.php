<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Revision;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Revision\DiffSession;
use Toolkit\DocGen\Model\Package\PackageGraph;
use Toolkit\DocGen\Model\ProjectModel;
use Toolkit\DocGen\Model\Reference\HierarchyIndex;
use Toolkit\DocGen\Model\Reference\SymbolTable;
use Toolkit\DocGen\Model\Reference\TestCaseIndex;
use Toolkit\DocGen\Model\Reference\UsageIndex;

/**
 * @covers \Toolkit\DocGen\Analysis\Revision\DiffSession
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Model\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Model\Package\PackageGraph
 * @uses \Toolkit\DocGen\Model\ProjectModel
 * @uses \Toolkit\DocGen\Model\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Model\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Model\Reference\UsageIndex
 */
#[CoversClass(DiffSession::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(UsageIndex::class)]
final class DiffSessionTest extends TestCase
{
    public function testStoresTheComparedModelTheIndexAndTheCheckouts(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/project', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $index = new DiffIndex('main', 'HEAD', '/tmp/base');

        $session = new DiffSession($model, $index, '/tmp/project', '/tmp/base', '/tmp/head');

        self::assertSame($model, $session->model);
        self::assertSame($index, $session->diff);
        self::assertSame('/tmp/project', $session->repositoryRoot);
        self::assertSame('/tmp/base', $session->basePath);
        self::assertSame('/tmp/head', $session->headPath);
    }

    public function testAComparisonAgainstTheWorkingTreeHasNoHeadCheckout(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/project', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        $session = new DiffSession($model, new DiffIndex('main', 'working tree'), '/tmp/project', '/tmp/base', null);

        self::assertNull($session->headPath);
        self::assertSame('working tree', $session->diff->headLabel());
    }
}
