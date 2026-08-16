<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffSession;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
