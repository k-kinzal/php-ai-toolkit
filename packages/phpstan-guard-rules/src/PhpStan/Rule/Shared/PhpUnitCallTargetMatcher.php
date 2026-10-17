<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;

/**
 * Matches PHPUnit assertion and helper calls made from test cases.
 */
final class PhpUnitCallTargetMatcher
{
    /**
     * Reports whether the method call target is $this.
     */
    public function isThisMethodCall(MethodCall $node): bool
    {
        return $node->var instanceof Variable
            && $node->var->name === 'this';
    }

    /**
     * Reports whether the static call targets PHPUnit Assert or TestCase.
     */
    public function isStaticCallOnPhpUnitAssert(StaticCall $node, Scope $scope): bool
    {
        if (!$node->class instanceof Name) {
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
    public function isStaticCallOnCurrentTestClass(StaticCall $node, Scope $scope): bool
    {
        if (!$node->class instanceof Name) {
            return false;
        }

        $className = $node->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return true;
        }

        return $scope->resolveName($node->class) === 'PHPUnit\\Framework\\TestCase';
    }
}
