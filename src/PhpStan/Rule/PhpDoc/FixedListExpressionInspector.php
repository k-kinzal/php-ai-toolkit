<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use function array_key_exists;
use function get_object_vars;
use function is_array;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Return_;

use function strtolower;

/**
 * Recognizes non-empty list literals owned by properties and callables.
 */
final class FixedListExpressionInspector
{
    /**
     * Reports whether an array is a non-empty literal with implicit list keys.
     */
    public function isNonEmptyList(Array_ $array): bool
    {
        if ($array->items === []) {
            return false;
        }

        foreach ($array->items as $item) {
            if (!$this->isImplicitArrayItem($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reports whether one parser-major-independent array item has an implicit key.
     */
    public function isImplicitArrayItem(?Node $item): bool
    {
        if ($item === null) {
            return false;
        }

        $properties = get_object_vars($item);

        return array_key_exists('key', $properties)
            && $properties['key'] === null
            && array_key_exists('unpack', $properties)
            && $properties['unpack'] === false;
    }

    /**
     * Reports whether a callable visibly returns list literals and no uncertain array expressions.
     */
    public function callableReturnsFixedLists(FunctionLike $callable): bool
    {
        $statements = $callable->getStmts();
        if ($statements === null) {
            return false;
        }

        $hasNonEmptyList = false;
        foreach ($this->ownedReturns($statements) as $return) {
            $expression = $return->expr;
            if ($expression === null) {
                continue;
            }
            if ($expression instanceof Array_) {
                if ($expression->items === []) {
                    continue;
                }
                if (!$this->isNonEmptyList($expression)) {
                    return false;
                }
                $hasNonEmptyList = true;
                continue;
            }
            if (!$this->isDefinitelyNonArray($expression)) {
                return false;
            }
        }

        return $hasNonEmptyList;
    }

    /**
     * Finds returns that belong to the callable instead of a nested scope.
     *
     * @param array<Node\Stmt> $statements
     * @return list<Return_>
     */
    public function ownedReturns(array $statements): array
    {
        $returns = [];
        foreach ($statements as $statement) {
            $this->collectReturns($statement, $returns);
        }

        return $returns;
    }

    /**
     * Collects return statements while pruning nested callable and class scopes.
     *
     * @param list<Return_> $returns
     */
    public function collectReturns(Node $node, array &$returns): void
    {
        if ($node instanceof FunctionLike || $node instanceof ClassLike) {
            return;
        }
        if ($node instanceof Return_) {
            $returns[] = $node;

            return;
        }

        foreach (get_object_vars($node) as $child) {
            if ($child instanceof Node) {
                $this->collectReturns($child, $returns);
                continue;
            }
            if (!is_array($child)) {
                continue;
            }
            foreach ($child as $nested) {
                if ($nested instanceof Node) {
                    $this->collectReturns($nested, $returns);
                }
            }
        }
    }

    /**
     * Reports expressions that cannot represent another array return branch.
     */
    public function isDefinitelyNonArray(Node\Expr $expression): bool
    {
        if ($expression instanceof Scalar || $expression instanceof New_ || $expression instanceof Closure || $expression instanceof ArrowFunction) {
            return true;
        }
        if (!$expression instanceof ConstFetch) {
            return false;
        }

        $name = strtolower($expression->name->toString());

        return $name === 'null' || $name === 'false' || $name === 'true';
    }
}
