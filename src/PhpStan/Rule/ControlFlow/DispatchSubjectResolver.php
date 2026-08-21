<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;

/**
 * Finds the expression a switch or match branches on.
 *
 * Usually that is the subject written next to the keyword. The other form is
 * `match (true)` and `switch (true)`, where the subject is a constant and every branch
 * carries its own condition; there the subject is whatever those conditions narrow, and
 * all of them have to narrow the same expression for the construct to be one dispatch.
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
