<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;

/**
 * Matches PHPUnit assertion and helper calls made from test cases.
 */
final class PhpUnitCallTargetMatcher
{
    /**
     * Reports whether the method call target is $this.
     */
    public function isThisMethodCall(\PhpParser\Node\Expr\MethodCall $node): bool
    {
        return $node->var instanceof \PhpParser\Node\Expr\Variable
            && $node->var->name === 'this';
    }

    /**
     * Reports whether the static call targets PHPUnit Assert or TestCase.
     */
    public function isStaticCallOnPhpUnitAssert(\PhpParser\Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if (!$node->class instanceof \PhpParser\Node\Name) {
            return false;
        }

        $className = $node->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return true;
        }

        return in_array($scope->resolveName($node->class), [
            'PHPUnit\\Framework\\Assert',
            'PHPUnit\\Framework\\TestCase',
        ], true);
    }

    /**
     * Reports whether the static call targets the current PHPUnit test class surface.
     */
    public function isStaticCallOnCurrentTestClass(\PhpParser\Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if (!$node->class instanceof \PhpParser\Node\Name) {
            return false;
        }

        $className = $node->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return true;
        }

        return $scope->resolveName($node->class) === 'PHPUnit\\Framework\\TestCase';
    }
}
