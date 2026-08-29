<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\RedundantDiagnostic;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

use function str_ends_with;
use function str_starts_with;

/**
 * Identifies diagnostics about control-flow constructs prohibited in test methods.
 */
final class TestControlFlowDiagnosticPolicy
{
    /**
     * Reports whether the diagnostic node belongs to prohibited test-method control flow.
     */
    public function isRedundant(Node $node, Scope $scope, bool $restrictedTestClass): bool
    {
        if (!$restrictedTestClass || !$this->isTestMethod($scope)) {
            return false;
        }

        return $node instanceof Node\Stmt\If_
            || $node instanceof Node\Stmt\ElseIf_
            || $node instanceof Node\Stmt\For_
            || $node instanceof Node\Stmt\Foreach_
            || $node instanceof Node\Stmt\While_
            || $node instanceof Node\Stmt\Do_
            || $node instanceof Node\Stmt\Switch_
            || $node instanceof Node\Expr\Match_;
    }

    /**
     * Reports whether the current reflected method is a PHPUnit test.
     */
    public function isTestMethod(Scope $scope): bool
    {
        $methodName = $scope->getFunctionName();
        if ($methodName === null) {
            return false;
        }

        if (str_starts_with($methodName, 'test')) {
            return true;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null || !$classReflection->hasNativeMethod($methodName)) {
            return false;
        }

        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            if ($method->getName() !== $methodName) {
                continue;
            }

            foreach ($method->getAttributes() as $attribute) {
                $name = $attribute->getName();
                if ($name === 'Test' || str_ends_with($name, '\\Test')) {
                    return true;
                }
            }
        }

        return false;
    }
}
