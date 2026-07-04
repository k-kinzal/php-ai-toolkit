<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class OverrideMustHaveAttributeRule implements Rule
{
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
        if ($this->hasOverrideAttribute($node)) {
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

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Override method %s() must have the #[\\Override] attribute.',
                    $methodName
                )
            )
                ->identifier('customRules.overrideMustHaveAttribute')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function hasOverrideAttribute(\PhpParser\Node\Stmt\ClassMethod $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $attr->name->toString();
                if ($name === 'Override' || $name === '\\Override') {
                    return true;
                }
            }
        }

        return false;
    }
}
