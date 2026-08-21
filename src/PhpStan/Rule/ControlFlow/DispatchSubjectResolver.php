<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use function count;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;

/**
 * Finds the expression a switch or match branches on.
 *
 * Usually that is the subject written next to the keyword. The other form is
 * `match (true)` and `switch (true)`, where the subject is a constant and every branch
 * carries its own condition; there the subject is whatever those conditions narrow, and
 * all of them have to narrow the same expression for the construct to be one dispatch.
 *
 * `match ($shape::class)` names its subject a third way: the construct branches on a class
 * name, and the object whose class that is decides which classes the branches have to
 * cover.
 */
final class DispatchSubjectResolver
{
    /** @readonly */
    private Standard $printer;

    /**
     * Creates a resolver that compares branch subjects by their printed form.
     */
    public function __construct(?Standard $printer = null)
    {
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Returns the expression the construct branches on, or null when it branches on nothing single.
     *
     * @param list<\PhpParser\Node\Expr> $branchConditions the condition written on each branch
     */
    public function resolve(\PhpParser\Node\Expr $condition, array $branchConditions, Scope $scope): ?\PhpParser\Node\Expr
    {
        $conditionType = $scope->getType($condition);
        if (!$conditionType->isTrue()->yes() && !$conditionType->isFalse()->yes()) {
            return $condition;
        }

        return $this->commonSubject($branchConditions);
    }

    /**
     * Returns the object whose class name a subject reads, or null when it reads none.
     *
     * `match ($shape::class)` and `switch (get_class($shape))` are the two ways PHP has of
     * saying "branch on which class this is". Both name the object in the subject, which is
     * what makes the set of classes the branches are answering for unambiguous.
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

    /**
     * Returns the expression every branch condition narrows, or null when they disagree.
     *
     * @param list<\PhpParser\Node\Expr> $branchConditions
     */
    public function commonSubject(array $branchConditions): ?\PhpParser\Node\Expr
    {
        $subject = null;
        $printedSubject = null;
        foreach ($branchConditions as $branchCondition) {
            $candidate = $this->narrowedExpression($branchCondition);
            if ($candidate === null) {
                return null;
            }

            $printedCandidate = $this->printer->prettyPrintExpr($candidate);
            if ($printedSubject === null) {
                $subject = $candidate;
                $printedSubject = $printedCandidate;

                continue;
            }

            if ($printedSubject !== $printedCandidate) {
                return null;
            }
        }

        return $subject;
    }

    /**
     * Returns the expression one condition narrows, or null when it narrows no single expression.
     */
    public function narrowedExpression(\PhpParser\Node\Expr $condition): ?\PhpParser\Node\Expr
    {
        if ($condition instanceof \PhpParser\Node\Expr\Instanceof_) {
            return $condition->expr;
        }

        if (
            !$condition instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && !$condition instanceof \PhpParser\Node\Expr\BinaryOp\NotIdentical
            && !$condition instanceof \PhpParser\Node\Expr\BinaryOp\Equal
            && !$condition instanceof \PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            return null;
        }

        $leftIsConstant = $this->isConstantExpression($condition->left);
        if ($leftIsConstant === $this->isConstantExpression($condition->right)) {
            return null;
        }

        return $leftIsConstant ? $condition->right : $condition->left;
    }

    /**
     * Reports whether an expression is a literal, a constant, or a class constant.
     */
    public function isConstantExpression(\PhpParser\Node\Expr $expression): bool
    {
        return $expression instanceof \PhpParser\Node\Scalar
            || $expression instanceof \PhpParser\Node\Expr\ConstFetch
            || $expression instanceof \PhpParser\Node\Expr\ClassConstFetch;
    }
}
