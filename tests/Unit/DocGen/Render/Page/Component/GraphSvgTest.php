<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \PhpAiToolkit\DocGen\Render\HtmlText
 */
#[CoversClass(GraphSvg::class)]
#[UsesClass(HtmlText::class)]
final class GraphSvgTest extends TestCase
{
    public function testRenderProducesLayeredSvgWithNodesAndEdges(): void
    {
        $nodes = [
            ['id' => 'app', 'label' => 'app', 'href' => 'app/index.html', 'kind' => 'pkg'],
            ['id' => 'lib', 'label' => 'lib', 'href' => null, 'kind' => 'pkg'],
        ];
        $edges = [['from' => 'app', 'to' => 'lib', 'kind' => 'require']];

        $svg = (new GraphSvg())->render($nodes, $edges);

        self::assertStringStartsWith('<svg class="graph" viewBox="0 0 88 176" role="img" style="max-width:88px">', $svg);
        self::assertStringContainsString('<a href="app/index.html"><rect class="node node-pkg" x="8" y="8" width="64" height="34" rx="7"/><text x="40" y="30">app</text></a>', $svg);
        self::assertStringContainsString('<rect class="node node-pkg" x="8" y="92" width="64" height="34" rx="7"/><text x="40" y="114">lib</text>', $svg);
        self::assertStringContainsString('<path class="edge edge-require"', $svg);
        self::assertStringContainsString('<circle class="edge-tip edge-require"', $svg);
        self::assertSame(1, substr_count($svg, '<a href='));
        self::assertStringEndsWith('</svg>', $svg);
    }

    public function testRenderReturnsEmptyStringWithoutNodes(): void
    {
        self::assertSame('', (new GraphSvg())->render([], []));
    }

    public function testEdgesSvgRendersCurvesAndSkipsUnknownEndpoints(): void
    {
        $positions = ['app' => ['x' => 8, 'y' => 8, 'w' => 64], 'lib' => ['x' => 8, 'y' => 92, 'w' => 64]];

        $svg = (new GraphSvg())->edgesSvg([['from' => 'app', 'to' => 'lib', 'kind' => 'require']], $positions);

        self::assertSame(
            '<path class="edge edge-require" d="M 40.0 42.0 C 40.0 72.0, 40.0 62.0, 40.0 89.0"/>'
            . '<circle class="edge-tip edge-require" cx="40.0" cy="90.0" r="2.6"/>',
            $svg,
        );
        self::assertSame('', (new GraphSvg())->edgesSvg([['from' => 'app', 'to' => 'ghost', 'kind' => 'require']], $positions));
    }

    public function testLayersPlacesDependentsAboveDependencies(): void
    {
        $nodes = [
            ['id' => 'app', 'label' => 'app', 'href' => null, 'kind' => 'pkg'],
            ['id' => 'lib', 'label' => 'lib', 'href' => null, 'kind' => 'pkg'],
        ];
        $edges = [['from' => 'app', 'to' => 'lib', 'kind' => 'require']];

        self::assertSame(['app' => 1, 'lib' => 0], (new GraphSvg())->layers($nodes, $edges));
    }

    public function testLayersGuardsAgainstDependencyCycles(): void
    {
        $nodes = [
            ['id' => 'a', 'label' => 'a', 'href' => null, 'kind' => 'pkg'],
            ['id' => 'b', 'label' => 'b', 'href' => null, 'kind' => 'pkg'],
        ];
        $edges = [
            ['from' => 'a', 'to' => 'b', 'kind' => 'require'],
            ['from' => 'b', 'to' => 'a', 'kind' => 'require'],
        ];

        self::assertSame(['a' => 2, 'b' => 3], (new GraphSvg())->layers($nodes, $edges));
    }

    public function testLongestPathComputesChainDepthAndStopsOnRevisit(): void
    {
        self::assertSame(0, (new GraphSvg())->longestPath('x', [], [], []));
        self::assertSame(2, (new GraphSvg())->longestPath('a', ['a' => ['b'], 'b' => ['c']], [], []));
        self::assertSame(5, (new GraphSvg())->longestPath('a', ['a' => ['b']], ['b' => 4], []));
        self::assertSame(1, (new GraphSvg())->longestPath('a', ['a' => ['a']], [], []));
    }

    public function testPositionsCentersRowsAndSortsRowsByLabel(): void
    {
        $nodes = [
            ['id' => 'a', 'label' => 'a', 'href' => null, 'kind' => 'pkg'],
            ['id' => 'c', 'label' => 'c', 'href' => null, 'kind' => 'pkg'],
            ['id' => 'b', 'label' => 'b', 'href' => null, 'kind' => 'pkg'],
        ];
        $layers = ['a' => 1, 'c' => 0, 'b' => 0];

        $positions = (new GraphSvg())->positions($nodes, $layers, 1);

        self::assertSame(['x' => 51, 'y' => 8, 'w' => 64], $positions['a']);
        self::assertSame(['x' => 8, 'y' => 92, 'w' => 64], $positions['b']);
        self::assertSame(['x' => 94, 'y' => 92, 'w' => 64], $positions['c']);
    }
}
