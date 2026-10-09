<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\ComposerManifest;
use Toolkit\DocGen\Analysis\Package\DiscoveredPackage;
use Toolkit\DocGen\Analysis\Package\PackageDependency;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\Package\PackageGraphBuilder;

/**
 * @covers \Toolkit\DocGen\Analysis\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Analysis\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Package\PackageDependency
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 */
#[CoversClass(PackageGraphBuilder::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(PackageDependency::class)]
#[UsesClass(PackageGraph::class)]
final class PackageGraphBuilderTest extends TestCase
{
    public function testBuildKeepsEdgesOnlyBetweenKnownPackages(): void
    {
        $a = new DiscoveredPackage(new ComposerManifest('/a', 'acme/a', '', [], [], ['acme/b' => '^1.0', 'external/dep' => '^2.0'], [], []), false);
        $b = new DiscoveredPackage(new ComposerManifest('/b', 'acme/b', '', [], [], [], [], []), false);

        $graph = (new PackageGraphBuilder())->build([$a, $b]);

        self::assertCount(1, $graph->edges);
        self::assertSame('acme/a', $graph->edges[0]->from);
        self::assertSame('acme/b', $graph->edges[0]->to);
        self::assertSame('require', $graph->edges[0]->kind);
    }

    public function testBuildRecordsRequireDevAndSuggestKinds(): void
    {
        $a = new DiscoveredPackage(new ComposerManifest('/a', 'acme/a', '', [], [], [], ['acme/b' => '^1.0'], ['acme/c' => 'Optional collaborator']), false);
        $b = new DiscoveredPackage(new ComposerManifest('/b', 'acme/b', '', [], [], [], [], []), false);
        $c = new DiscoveredPackage(new ComposerManifest('/c', 'acme/c', '', [], [], [], [], []), true);

        $graph = (new PackageGraphBuilder())->build([$a, $b, $c]);

        self::assertCount(2, $graph->edges);
        self::assertSame('acme/a', $graph->edges[0]->from);
        self::assertSame('acme/b', $graph->edges[0]->to);
        self::assertSame('require-dev', $graph->edges[0]->kind);
        self::assertSame('acme/a', $graph->edges[1]->from);
        self::assertSame('acme/c', $graph->edges[1]->to);
        self::assertSame('suggest', $graph->edges[1]->kind);
    }

    public function testBuildExcludesSelfEdges(): void
    {
        $a = new DiscoveredPackage(new ComposerManifest('/a', 'acme/a', '', [], [], ['acme/a' => '@dev'], [], []), false);

        self::assertSame([], (new PackageGraphBuilder())->build([$a])->edges);
    }

    public function testBuildReturnsEmptyGraphForNoPackages(): void
    {
        self::assertSame([], (new PackageGraphBuilder())->build([])->edges);
    }
}
