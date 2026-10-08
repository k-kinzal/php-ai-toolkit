<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Package\PackageDependency;
use Toolkit\DocGen\Model\Package\PackageGraph;

/**
 * @covers \Toolkit\DocGen\Model\Package\PackageGraph
 * @uses \Toolkit\DocGen\Model\Package\PackageDependency
 */
#[CoversClass(PackageGraph::class)]
#[UsesClass(PackageDependency::class)]
final class PackageGraphTest extends TestCase
{
    public function testStoresEdgeList(): void
    {
        $edges = [
            new PackageDependency('acme/a', 'acme/b', 'require'),
            new PackageDependency('acme/b', 'acme/c', 'suggest'),
        ];

        self::assertSame($edges, (new PackageGraph($edges))->edges);
    }

    public function testStoresEmptyEdgeList(): void
    {
        self::assertSame([], (new PackageGraph([]))->edges);
    }
}
