<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;

/**
 * Resolves direct Foo::class expressions to fully qualified class names.
 */
final class ClassStringExpressionResolver
{
    /**
     * Returns the fully qualified name represented by a direct class-string expression.
     */
    public function resolve(Expr $expression, Scope $scope): ?string
    {
        if (!$expression instanceof ClassConstFetch) {
            return null;
        }

        if (!$expression->name instanceof Identifier) {
            return null;
        }

        if ($expression->name->toString() !== 'class') {
            return null;
        }

        if (!$expression->class instanceof Name) {
            return null;
        }

        return $scope->resolveName($expression->class);
    }
}
