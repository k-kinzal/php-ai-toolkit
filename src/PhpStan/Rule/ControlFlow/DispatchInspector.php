<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\Type;

/**
 * Inspects one switch statement or match expression for the values it leaves out.
 *
 * A match without a "default" arm is left to PHPStan, which already reports the values such
 * a match does not handle; reporting them a second time would only duplicate that error.
 * Everything else — a switch either way, and a match whose "default" hides the gap — is
 * inspected here.
 */
final class DispatchInspector
{
    /** @readonly */
    private DispatchSubjectResolver $subjectResolver;

    /** @readonly */
    private ClosedTypeVariants $closedTypeVariants;

    /** @readonly */
    private UnhandledVariantFinder $unhandledFinder;

    /** @readonly */
    private RequireExhaustiveDispatchErrorBuilder $errorBuilder;

    /**
     * Creates an inspector from the subject, variant, and error-building collaborators.
     */
    public function __construct(
        ?DispatchSubjectResolver $subjectResolver = null,
        ?ClosedTypeVariants $closedTypeVariants = null,
        ?UnhandledVariantFinder $unhandledFinder = null,
        ?RequireExhaustiveDispatchErrorBuilder $errorBuilder = null,
    ) {
        $this->subjectResolver = $subjectResolver ?? new DispatchSubjectResolver();
        $this->closedTypeVariants = $closedTypeVariants ?? new ClosedTypeVariants();
        $this->unhandledFinder = $unhandledFinder ?? new UnhandledVariantFinder();
        $this->errorBuilder = $errorBuilder ?? new RequireExhaustiveDispatchErrorBuilder();
    }

    /**
     * Returns the error of a match expression whose "default" arm absorbs closed values.
     *
     * @return list<IdentifierRuleError>
     */
    public function matchErrors(\PhpParser\Node\Expr\Match_ $node, Scope $scope): array
    {
        $armConditions = [];
        $narrowings = [];
        $hasDefaultArm = false;
        foreach ($node->arms as $arm) {
            if ($arm->conds === null || $arm->conds === []) {
                $hasDefaultArm = true;

                continue;
            }

            foreach ($arm->conds as $armCondition) {
                $armConditions[] = $armCondition;
                $narrowings[] = new Identical($node->cond, $armCondition);
            }
        }

        if (!$hasDefaultArm) {
            return [];
        }

        $unhandled = $this->unhandled($node->cond, $armConditions, $narrowings, $scope);
        if ($unhandled === []) {
            return [];
        }

        return [$this->errorBuilder->buildMatchCatchAll($this->errorBuilder->labels($unhandled), null, $node->getStartLine())];
    }

    /**
     * Returns the error of a switch statement that leaves values of a closed subject out.
     *
     * @return list<IdentifierRuleError>
     */
    public function switchErrors(\PhpParser\Node\Stmt\Switch_ $node, Scope $scope): array
    {
        $caseConditions = [];
        $narrowings = [];
        $hasDefaultCase = false;
        foreach ($node->cases as $case) {
            if ($case->cond === null) {
                $hasDefaultCase = true;

                continue;
            }

            $caseConditions[] = $case->cond;
            $narrowings[] = new Equal($node->cond, $case->cond);
        }

        $unhandled = $this->unhandled($node->cond, $caseConditions, $narrowings, $scope);
        if ($unhandled === []) {
            return [];
        }

        if ($hasDefaultCase) {
            return [$this->errorBuilder->buildSwitchCatchAll($this->errorBuilder->labels($unhandled), null, $node->getStartLine())];
        }

        return [$this->errorBuilder->buildSwitchUnhandled($this->errorBuilder->labels($unhandled), null, $node->getStartLine())];
    }

    /**
     * Returns the values of the closed subject that no branch of the dispatch claims.
     *
     * A union of classes only stands in for a sealed hierarchy where the branches carry
     * their own conditions, as `match (true)` does: written next to the keyword, an object
     * subject is compared for identity rather than dispatched on, and its class list says
     * nothing about which branch runs.
     *
     * @param list<\PhpParser\Node\Expr> $branchConditions
     * @param list<\PhpParser\Node\Expr> $narrowings
     * @return list<Type>
     */
    public function unhandled(\PhpParser\Node\Expr $condition, array $branchConditions, array $narrowings, Scope $scope): array
    {
        $subject = $this->subjectResolver->resolve($condition, $branchConditions, $scope);
        if ($subject === null) {
            return [];
        }

        $subjectType = $scope->getType($subject);
        $variants = $this->closedTypeVariants->values($subjectType);
        if ($variants === null && $subject !== $condition) {
            $variants = $this->closedTypeVariants->objects($subjectType);
        }

        if ($variants === null) {
            return [];
        }

        return $this->unhandledFinder->find($scope, $subject, $narrowings, $variants);
    }
}
