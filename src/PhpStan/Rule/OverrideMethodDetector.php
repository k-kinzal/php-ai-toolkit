<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\TrinaryLogic;

/**
 * Detects methods that intentionally override parent methods.
 */
final class OverrideMethodDetector
{
    /**
     * Creates a detector from Override attribute detection.
     */
    private readonly OverrideAttributeDetector $overrideAttributeDetector;

    /**
     * Creates a detector from Override attribute detection.
     */
    public function __construct(?OverrideAttributeDetector $overrideAttributeDetector = null)
    {
        $this->overrideAttributeDetector = $overrideAttributeDetector ?? new OverrideAttributeDetector();
    }

    /**
     * Reports whether the class method is an override allowed by test class rules.
     */
    public function isOverride(\PhpParser\Node\Stmt\ClassMethod $node, Scope $scope): bool
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

        return $this->overrideAttributeDetector->has($node);
    }
}
