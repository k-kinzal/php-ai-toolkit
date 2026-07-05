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
    private readonly OverrideAttributeDetector $overrideAttributeDetector;

    /**
     * Creates a rule from override attribute detection.
     */
    public function __construct(?OverrideAttributeDetector $overrideAttributeDetector = null)
    {
        $this->overrideAttributeDetector = $overrideAttributeDetector ?? new OverrideAttributeDetector();
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
}
