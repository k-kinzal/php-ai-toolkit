<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;

/**
 * Resolves direct Foo::class expressions to fully qualified class names.
 */
final class ClassStringExpressionResolver
{
    /**
     * Returns the fully qualified name represented by a direct class-string expression.
     */
    public function resolve(\PhpParser\Node\Expr $expression, Scope $scope): ?string
    {
        if (!$expression instanceof \PhpParser\Node\Expr\ClassConstFetch) {
            return null;
        }

        if (!$expression->name instanceof \PhpParser\Node\Identifier) {
            return null;
        }

        if ($expression->name->toString() !== 'class') {
            return null;
        }

        if (!$expression->class instanceof \PhpParser\Node\Name) {
            return null;
        }

        return $scope->resolveName($expression->class);
    }
}
