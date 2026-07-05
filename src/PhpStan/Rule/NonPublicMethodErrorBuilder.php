<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds PHPStan errors for non-public method design violations.
 */
final class NonPublicMethodErrorBuilder
{
    /** @readonly */
    private ClassLikeNameResolver $classLikeNameResolver;

    /**
     * Creates the error builder from a class-like name resolver.
     */
    public function __construct(?ClassLikeNameResolver $classLikeNameResolver = null)
    {
        $this->classLikeNameResolver = $classLikeNameResolver ?? new ClassLikeNameResolver();
    }

    /**
     * Builds an error for a private method.
     */
    public function privateMethod(
        \PhpParser\Node\Stmt\ClassMethod $method,
        \PhpParser\Node\Stmt\ClassLike $classLike,
        Scope $scope,
    ): IdentifierRuleError {
        return RuleErrorBuilder::message(
            sprintf(
                'Move private method %s() out of %s into a focused collaborator, or make it public only if it is part of this type\'s API.',
                $method->name->toString(),
                $this->classLikeNameResolver->resolve($classLike, $scope),
            )
        )
            ->identifier('customRules.nonPublicMethod')
            ->line($method->getStartLine())
            ->build();
    }

    /**
     * Builds an error for a protected method in a concrete non-override class.
     */
    public function protectedMethod(
        \PhpParser\Node\Stmt\ClassMethod $method,
        \PhpParser\Node\Stmt\ClassLike $classLike,
        Scope $scope,
    ): IdentifierRuleError {
        return RuleErrorBuilder::message(
            sprintf(
                'Move protected method %s() out of concrete class %s, or put the extension point on an abstract class, trait, or override method.',
                $method->name->toString(),
                $this->classLikeNameResolver->resolve($classLike, $scope),
            )
        )
            ->identifier('customRules.nonPublicMethod')
            ->line($method->getStartLine())
            ->build();
    }
}
