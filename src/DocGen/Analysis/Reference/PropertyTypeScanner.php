<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function is_string;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;

use function strtolower;

/**
 * Scans the statically certain property types of one class-like node.
 *
 * Only single class-name declarations count, from typed properties and
 * promoted constructor parameters, so receiver resolution stays exact.
 */
final class PropertyTypeScanner
{
    /**
     * Returns the property class types keyed by lowercased property name.
     *
     * @return array<string, string>
     */
    public function scan(ClassLike $node): array
    {
        $props = [];
        foreach ($node->stmts as $statement) {
            if ($statement instanceof Property && $statement->type instanceof Name) {
                foreach ($statement->props as $item) {
                    $props[strtolower($item->name->toString())] = $statement->type->toString();
                }
            }

            if ($statement instanceof ClassMethod && $statement->name->toLowerString() === '__construct') {
                foreach ($statement->params as $param) {
                    if ($param->flags !== 0 && $param->type instanceof Name && $param->var instanceof Variable && is_string($param->var->name)) {
                        $props[strtolower($param->var->name)] = $param->type->toString();
                    }
                }
            }
        }

        return $props;
    }
}
