<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page\Component;

use function array_fill;
use function array_keys;
use function count;
use function max;

use PhpAiToolkit\DocGen\Render\HtmlText;

use function sprintf;
use function strlen;
use function usort;

/**
 * Renders a layered directed graph as inline SVG.
 *
 * Nodes are layered by their longest dependency path, so dependents sit
 * above their dependencies and the arrows read top-down.
 */
final class GraphSvg
{
    /** @readonly */
    private HtmlText $escaper;

    /**
     * Creates a graph renderer.
     */
    public function __construct(?HtmlText $escaper = null)
    {
        $this->escaper = $escaper ?? new HtmlText();
    }

    /**
     * Renders one graph.
     *
     * @param list<array{id: string, label: string, href: ?string, kind: string}> $nodes
     * @param list<array{from: string, to: string, kind: string}> $edges
     */
    public function render(array $nodes, array $edges): string
    {
        if ($nodes === []) {
            return '';
        }

        $layers = $this->layers($nodes, $edges);
        $maxLayer = 0;
        foreach ($layers as $layer) {
            $maxLayer = max($maxLayer, $layer);
        }

        $positions = $this->positions($nodes, $layers, $maxLayer);
        $width = 0;
        foreach ($positions as $box) {
            $width = max($width, $box['x'] + $box['w'] + 16);
        }

        $height = ($maxLayer + 1) * 84 + 8;
        $svg = sprintf('<svg class="graph" viewBox="0 0 %d %d" role="img" style="max-width:%dpx">', $width, $height, $width);
        $svg .= $this->edgesSvg($edges, $positions);
        foreach ($nodes as $node) {
            $box = $positions[$node['id']];
            $inner = sprintf(
                '<rect class="node node-%s" x="%d" y="%d" width="%d" height="34" rx="7"/><text x="%d" y="%d">%s</text>',
                $this->escaper->e($node['kind']),
                $box['x'],
                $box['y'],
                $box['w'],
                $box['x'] + $box['w'] / 2,
                $box['y'] + 22,
                $this->escaper->e($node['label']),
            );
            $svg .= $node['href'] !== null ? sprintf('<a href="%s">%s</a>', $this->escaper->e($node['href']), $inner) : $inner;
        }

        return $svg . '</svg>';
    }

    /**
     * Renders the curved edge paths between positioned nodes.
     *
     * @param list<array{from: string, to: string, kind: string}> $edges
     * @param array<string, array{x: int, y: int, w: int}> $positions
     */
    public function edgesSvg(array $edges, array $positions): string
    {
        $svg = '';
        foreach ($edges as $edge) {
            $from = $positions[$edge['from']] ?? null;
            $to = $positions[$edge['to']] ?? null;
            if ($from === null || $to === null) {
                continue;
            }

            $x1 = $from['x'] + $from['w'] / 2;
            $y1 = $from['y'] + 34;
            $x2 = $to['x'] + $to['w'] / 2;
            $y2 = $to['y'];
            $svg .= sprintf(
                '<path class="edge edge-%s" d="M %.1f %.1f C %.1f %.1f, %.1f %.1f, %.1f %.1f"/>',
                $this->escaper->e($edge['kind']),
                $x1,
                $y1,
                $x1,
                $y1 + 30,
                $x2,
                $y2 - 30,
                $x2,
                $y2 - 3,
            );
            $svg .= sprintf('<circle class="edge-tip edge-%s" cx="%.1f" cy="%.1f" r="2.6"/>', $this->escaper->e($edge['kind']), $x2, $y2 - 2);
        }

        return $svg;
    }

    /**
     * Computes the layer of every node from its longest outgoing path.
     *
     * @param list<array{id: string, label: string, href: ?string, kind: string}> $nodes
     * @param list<array{from: string, to: string, kind: string}> $edges
     *
     * @return array<string, int>
     */
    public function layers(array $nodes, array $edges): array
    {
        $out = [];
        foreach ($edges as $edge) {
            $out[$edge['from']][] = $edge['to'];
        }

        $layers = [];
        foreach ($nodes as $node) {
            $layers[$node['id']] = $this->longestPath($node['id'], $out, $layers, []);
        }

        return $layers;
    }

    /**
     * Computes the longest path length from one node, bounding cycles by the graph size.
     *
     * @param array<string, list<string>> $out
     * @param array<string, int> $known
     * @param array<string, bool> $visiting
     */
    public function longestPath(string $id, array $out, array $known, array $visiting): int
    {
        if (isset($known[$id])) {
            return $known[$id];
        }

        if (isset($visiting[$id])) {
            return 0;
        }

        $nodeIds = [$id => true];
        foreach ($out as $from => $targets) {
            $nodeIds[$from] = true;
            foreach ($targets as $target) {
                $nodeIds[$target] = true;
            }
        }

        $depths = $known;
        foreach (array_keys($nodeIds) as $nodeId) {
            $depths[$nodeId] ??= 0;
        }

        foreach (array_fill(0, count($nodeIds), null) as $_) {
            $nextDepths = $depths;
            foreach ($out as $from => $targets) {
                if (isset($known[$from])) {
                    continue;
                }

                $depth = 0;
                foreach ($targets as $target) {
                    $depth = max($depth, 1 + $depths[$target]);
                }

                $nextDepths[$from] = $depth;
            }

            $depths = $nextDepths;
        }

        return $depths[$id];
    }

    /**
     * Computes node boxes, centering each layer horizontally.
     *
     * @param list<array{id: string, label: string, href: ?string, kind: string}> $nodes
     * @param array<string, int> $layers
     *
     * @return array<string, array{x: int, y: int, w: int}>
     */
    public function positions(array $nodes, array $layers, int $maxLayer): array
    {
        $rows = [];
        foreach ($nodes as $node) {
            $rows[$layers[$node['id']]][] = $node;
        }

        $rowWidths = [];
        $totalWidth = 0;
        foreach ($rows as $layer => $rowNodes) {
            usort($rowNodes, static fn (array $a, array $b): int => $a['label'] <=> $b['label']);
            $rows[$layer] = $rowNodes;
            $width = 0;
            foreach ($rowNodes as $node) {
                $width += max(64, 8 * strlen($node['label']) + 26) + 22;
            }

            $rowWidths[$layer] = $width - 22;
            $totalWidth = max($totalWidth, $rowWidths[$layer]);
        }

        $positions = [];
        foreach ($rows as $layer => $rowNodes) {
            $x = (int) (8 + ($totalWidth - $rowWidths[$layer]) / 2);
            $y = ($maxLayer - $layer) * 84 + 8;
            foreach ($rowNodes as $node) {
                $width = max(64, 8 * strlen($node['label']) + 26);
                $positions[$node['id']] = ['x' => $x, 'y' => $y, 'w' => $width];
                $x += $width + 22;
            }
        }

        return $positions;
    }
}
