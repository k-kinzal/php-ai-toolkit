<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class NoHelperMethodInTestClassRule implements Rule
{
    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
    ) {
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
        if (!$this->testClassScope->isRestrictedTestClass($scope)) {
            return [];
        }

        $methodName = $node->name->toString();

        if ($this->isTestMethod($node)) {
            return [];
        }

        if (str_starts_with($methodName, 'provider')) {
            return [];
        }

        if ($this->isOverride($node, $scope)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        $className = $classReflection !== null ? $classReflection->getName() : '(unknown)';

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Method %s() is not an override in %s. Test classes should only contain test methods and framework overrides. Move helper logic to a dedicated class or inline it into the test method.',
                    $methodName,
                    $className
                )
            )
                ->identifier('customRules.testClassNonOverrideMethod')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function isTestMethod(\PhpParser\Node\Stmt\ClassMethod $node): bool
    {
        if (str_starts_with($node->name->toString(), 'test')) {
            return true;
        }

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                if ($attrName === 'Test' || str_ends_with($attrName, '\\Test')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isOverride(\PhpParser\Node\Stmt\ClassMethod $node, Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null) {
            return false;
        }

        $methodName = $node->name->toString();
        if (!$parentClass->hasMethod($methodName)) {
            return false;
        }

        $parentMethod = $parentClass->getMethod($methodName, $scope);
        $isAbstract = $parentMethod->isAbstract();
        if ($isAbstract instanceof TrinaryLogic ? $isAbstract->yes() : $isAbstract === true) {
            return true;
        }

        return $this->hasOverrideAttribute($node);
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
