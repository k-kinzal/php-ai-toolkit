<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;

/**
 * Resolves class-like names for rule messages.
 */
final class ClassLikeNameResolver
{
    /**
     * Resolves a displayable class-like name from the AST and current namespace.
     */
    public function resolve(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): string
    {
        if (isset($node->namespacedName)) {
            return $node->namespacedName->toString();
        }

        if ($node->name === null) {
            return '(anonymous class)';
        }

        $namespace = $scope->getNamespace();
        if ($namespace === null) {
            return $node->name->toString();
        }

        return $namespace . '\\' . $node->name->toString();
    }
}
