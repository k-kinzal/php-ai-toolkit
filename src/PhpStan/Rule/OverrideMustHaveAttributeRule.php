<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\OverrideMustHaveAttribute\OverridableMethodPolicy;
use PhpAiToolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;

/**
 * Requires #[\Override] on methods that override a non-abstract parent method.
 *
 * Implementing an abstract parent method is an implementation rather than an
 * override, so it is not reported. Neither are the methods PHP refuses the
 * attribute on; see OverridableMethodPolicy for which ones those are and why.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class OverrideMustHaveAttributeRule implements Rule
{
    /** @readonly */
    private OverrideAttributeDetector $overrideAttributeDetector;

    /** @readonly */
    private OverridableMethodPolicy $overridableMethodPolicy;

    /**
     * Creates a rule from override attribute detection and the override policy.
     */
    public function __construct(
        ?OverrideAttributeDetector $overrideAttributeDetector = null,
        ?OverridableMethodPolicy $overridableMethodPolicy = null,
    ) {
        $this->overrideAttributeDetector = $overrideAttributeDetector ?? new OverrideAttributeDetector();
        $this->overridableMethodPolicy = $overridableMethodPolicy ?? new OverridableMethodPolicy();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassMethod>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($this->overrideAttributeDetector->has($node)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$parentClass->hasMethod($methodName)) {
            return [];
        }

        $parentMethod = $parentClass->getMethod($methodName, $scope);
        $isAbstract = $parentMethod->isAbstract();
        if ($isAbstract instanceof TrinaryLogic ? $isAbstract->yes() : $isAbstract === true) {
            return [];
        }

        if (!$this->overridableMethodPolicy->allows($methodName, $parentMethod)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Add #[\\Override] to override method %s().',
                    $methodName
                )
            )
                ->identifier('customRules.overrideMustHaveAttribute')
                ->line($node->getStartLine())
            ->build(),
        ];
    }
}
