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
        $narrowings = [];
        $hasDefaultArm = false;
        foreach ($node->arms as $arm) {
            if ($arm->conds === null || $arm->conds === []) {
                $hasDefaultArm = true;

                continue;
            }

            foreach ($arm->conds as $armCondition) {
                $narrowings[] = new Identical($node->cond, $armCondition);
            }
        }

        if (!$hasDefaultArm) {
            return [];
        }

        $unhandled = $this->unhandled($node->cond, $narrowings, $scope);
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
        $narrowings = [];
        $hasDefaultCase = false;
        foreach ($node->cases as $case) {
            if ($case->cond === null) {
                $hasDefaultCase = true;

                continue;
            }

            $narrowings[] = new Equal($node->cond, $case->cond);
        }

        $unhandled = $this->unhandled($node->cond, $narrowings, $scope);
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
     * @param list<\PhpParser\Node\Expr> $narrowings
     * @return list<Type>
     */
    public function unhandled(\PhpParser\Node\Expr $condition, array $narrowings, Scope $scope): array
    {
        $subject = $this->subjectResolver->resolve($condition, $scope);
        if ($subject === null) {
            return [];
        }

        $variants = $this->closedTypeVariants->values($scope->getType($subject));
        if ($variants === null) {
            return [];
        }

        return $this->unhandledFinder->find($scope, $subject, $narrowings, $variants);
    }
}
