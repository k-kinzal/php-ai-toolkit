<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ControlFlow;

use function count;

use PHPStan\Analyser\Scope;

/**
 * Finds the expression a switch or match states that it branches on.
 *
 * Exhaustiveness can only be asked of a construct that says what it is answering for, and
 * PHP has two ways of saying it: the subject written next to the keyword, and the class
 * name of an object read in the subject. `match (true)` says neither. Its subject is a
 * constant and every branch carries a condition of its own, so an arm may test anything at
 * all and no set of values can be demanded of the table.
 */
final class DispatchSubjectResolver
{
    /**
     * Returns the expression the construct branches on, or null when it names none.
     */
    public function resolve(\PhpParser\Node\Expr $condition, Scope $scope): ?\PhpParser\Node\Expr
    {
        $conditionType = $scope->getType($condition);
        if ($conditionType->isTrue()->yes() || $conditionType->isFalse()->yes()) {
            return null;
        }

        return $condition;
    }

    /**
     * Returns the object whose class name a subject reads, or null when it reads none.
     *
     * `match ($shape::class)` and `switch (get_class($shape))` are the two ways PHP has of
     * saying "branch on which class this is". Both name the object in the subject, and a
     * branch of such a table can hold nothing but a class name, which is what makes the set
     * of classes it is answering for unambiguous.
     */
    public function namedObject(\PhpParser\Node\Expr $condition): ?\PhpParser\Node\Expr
    {
        if (
            $condition instanceof \PhpParser\Node\Expr\ClassConstFetch
            && $condition->class instanceof \PhpParser\Node\Expr
            && $condition->name instanceof \PhpParser\Node\Identifier
            && $condition->name->toLowerString() === 'class'
        ) {
            return $condition->class;
        }

        if (
            $condition instanceof \PhpParser\Node\Expr\FuncCall
            && $condition->name instanceof \PhpParser\Node\Name
            && $condition->name->toLowerString() === 'get_class'
            && count($condition->args) === 1
            && $condition->args[0] instanceof \PhpParser\Node\Arg
        ) {
            return $condition->args[0]->value;
        }

        return null;
    }
}
