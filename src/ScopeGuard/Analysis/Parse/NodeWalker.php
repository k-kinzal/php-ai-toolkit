<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Parse;

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
     * @param array<array-key, \PhpParser\Node|array<array-key, \PhpParser\Node>> $nodes
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
     * @return list<\PhpParser\Node|list<\PhpParser\Node>>
     */
    public function children(\PhpParser\Node $node): array
    {
        $values = get_object_vars($node);
        $children = [];
        foreach ($node->getSubNodeNames() as $name) {
            $value = $values[$name] ?? null;
            if ($value instanceof \PhpParser\Node) {
                $children[] = $value;
                continue;
            }
            if (!is_array($value)) {
                continue;
            }

            $nestedNodes = [];
            foreach ($value as $nested) {
                if ($nested instanceof \PhpParser\Node) {
                    $nestedNodes[] = $nested;
                }
            }
            $children[] = $nestedNodes;
        }

        return $children;
    }
}
