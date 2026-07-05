<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;

/**
 * Removes nodes that belong to nested closures, arrow functions, or classes.
 */
final class NestedScopeFilter
{
    /**
     * @param array<\PhpParser\Node> $violations
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @return list<\PhpParser\Node>
     */
    public function filter(array $violations, array $stmts): array
    {
        $nodeFinder = new NodeFinder();

        /** @var list<Closure|ArrowFunction|Class_> $nestedScopes */
        $nestedScopes = $nodeFinder->find($stmts, static function (\PhpParser\Node $node): bool {
            return $node instanceof Closure || $node instanceof ArrowFunction || $node instanceof Class_;
        });

        $result = [];
        foreach ($violations as $violation) {
            if (!$this->contains($violation, $nestedScopes)) {
                $result[] = $violation;
            }
        }

        return $result;
    }

    /**
     * @param list<Closure|ArrowFunction|Class_> $scopes
     */
    public function contains(\PhpParser\Node $node, array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($node->getStartLine() >= $scope->getStartLine() && $node->getEndLine() <= $scope->getEndLine()) {
                return true;
            }
        }

        return false;
    }
}
