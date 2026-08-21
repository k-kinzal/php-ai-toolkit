<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Parse;

use function get_object_vars;
use function is_array;

/**
 * Flattens a parsed statement tree into every node it contains.
 *
 * ScopeGuard walks the tree itself instead of implementing a node visitor,
 * because the visitor interface signature differs between the supported
 * nikic/php-parser majors while the node accessors do not.
 *
 * @visibility parent
 */
final class NodeWalker
{
    /**
     * Returns the given nodes and every node below them, in source order.
     *
     * @param array<mixed> $nodes
     * @return list<\PhpParser\Node>
     */
    public function walk(array $nodes): array
    {
        $collected = [];
        foreach ($nodes as $node) {
            if (is_array($node)) {
                foreach ($this->walk($node) as $nested) {
                    $collected[] = $nested;
                }
                continue;
            }

            if (!$node instanceof \PhpParser\Node) {
                continue;
            }

            $collected[] = $node;
            foreach ($this->walk($this->children($node)) as $child) {
                $collected[] = $child;
            }
        }

        return $collected;
    }

    /**
     * Returns the direct sub-node values of one node.
     *
     * @return array<mixed>
     */
    public function children(\PhpParser\Node $node): array
    {
        $values = get_object_vars($node);
        $children = [];
        foreach ($node->getSubNodeNames() as $name) {
            $children[] = $values[$name] ?? null;
        }

        return $children;
    }
}
