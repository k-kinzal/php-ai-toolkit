<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;

/**
 * Detects anonymous class-like nodes for PHPDoc rules.
 */
final class AnonymousClassDetector
{
    /**
     * Reports whether the class-like node represents an anonymous class.
     */
    public function isAnonymous(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): bool
    {
        if (!$node instanceof \PhpParser\Node\Stmt\Class_) {
            return false;
        }

        if ($node->name === null) {
            return true;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection !== null && $classReflection->isAnonymous()) {
            return true;
        }

        return str_starts_with($node->name->toString(), 'AnonymousClass');
    }
}
