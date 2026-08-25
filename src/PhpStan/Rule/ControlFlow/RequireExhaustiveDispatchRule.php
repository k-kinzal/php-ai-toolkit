<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Requires a switch or match over a closed type to name a branch for every value of it.
 *
 * Rust, Kotlin, and Swift refuse to compile a match that leaves a variant of a closed type
 * out, which is what makes adding a variant safe: the compiler lists the places that have
 * to be looked at again. PHP has no such check, and its catch-all branches remove even the
 * runtime error, so an enum case added today reaches "default" everywhere tomorrow and the
 * program keeps running with the wrong answer.
 *
 * This rule reads the closed set of values out of the subject's type, so no annotation is
 * needed: an enum, a bool, a union of literals, and a union of classes each carry the list
 * already. A match without a "default" arm is left to PHPStan's own match.unhandled error.
 *
 * @implements Rule<\PhpParser\Node>
 */
final class RequireExhaustiveDispatchRule implements Rule
{
    /** @readonly */
    private DispatchInspector $inspector;

    /**
     * Creates the rule from the inspector that decides which values a dispatch leaves out.
     */
    public function __construct(?DispatchInspector $inspector = null)
    {
        $this->inspector = $inspector ?? new DispatchInspector();
    }

    /**
     * @return class-string<\PhpParser\Node>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
    }

    /**
     * @param \PhpParser\Node $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node instanceof \PhpParser\Node\Expr\Match_) {
            return $this->inspector->matchErrors($node, $scope);
        }

        if ($node instanceof \PhpParser\Node\Stmt\Switch_) {
            return $this->inspector->switchErrors($node, $scope);
        }

        return [];
    }
}
