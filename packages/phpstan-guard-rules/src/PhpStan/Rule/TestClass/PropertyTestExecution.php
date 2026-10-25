<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestClass;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;

/**
 * Recognizes executed Eris quantifiers without counting nested callback bodies.
 */
final class PropertyTestExecution
{
    /**
     * Accepts chained calls and quantifiers stored in local variables.
     */
    public function runsEris(ClassMethod $method): bool
    {
        $quantifiers = [];
        foreach ($method->stmts ?? [] as $statement) {
            $expression = $statement instanceof Expression || $statement instanceof Return_ ? $statement->expr : null;
            if ($expression !== null && $this->executes($expression, $quantifiers)) {
                return true;
            }
            if ($expression instanceof Assign && $expression->var instanceof Variable && is_string($expression->var->name)) {
                $quantifiers[$expression->var->name] = $this->isQuantifier($expression->expr, $quantifiers);
            }
            if ($statement instanceof Return_) {
                break;
            }
        }
        return false;
    }

    /**
     * Recognizes both supported Eris execution APIs, including an assigned result.
     * @param array<string, bool> $quantifiers
     */
    public function executes(Expr $expression, array $quantifiers): bool
    {
        if ($expression instanceof Assign) {
            return $this->executes($expression->expr, $quantifiers);
        }
        if ($expression instanceof FuncCall && $expression->name instanceof Expr) {
            return !$expression->isFirstClassCallable() && $expression->args !== [] && $this->isQuantifier($expression->name, $quantifiers);
        }
        return $expression instanceof MethodCall && !$expression->isFirstClassCallable() && $expression->name instanceof Identifier && in_array(strtolower($expression->name->toString()), ['then', '__invoke'], true) && $expression->args !== [] && $this->isQuantifier($expression->var, $quantifiers);
    }

    /**
     * Follows Eris's fluent quantifier configuration back to a generator call.
     * @param array<string, bool> $quantifiers
     */
    public function isQuantifier(Expr $expression, array $quantifiers): bool
    {
        if ($expression instanceof Variable && is_string($expression->name)) {
            return $quantifiers[$expression->name] ?? false;
        }
        if (!$expression instanceof MethodCall || $expression->isFirstClassCallable() || !$expression->name instanceof Identifier) {
            return false;
        }
        if (strtolower($expression->name->toString()) === 'forall') {
            return $expression->var instanceof Variable && $expression->var->name === 'this' && count($expression->args) > 0;
        }
        return in_array(strtolower($expression->name->toString()), ['when', 'and', 'withmaxsize', 'hook', 'stopon', 'disableshrinking'], true) && $this->isQuantifier($expression->var, $quantifiers);
    }
}
