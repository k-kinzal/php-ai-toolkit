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
    private readonly ClassLikeNameResolver $classLikeNameResolver;

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
                'Private method %s() is prohibited in %s. Private behavior hides a responsibility inside the class; extract that behavior to a focused collaborator with a public API, or make it public only when it is part of this type\'s own responsibility.',
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
                'Protected method %s() is prohibited in concrete class %s. Protected methods are allowed only in abstract classes, traits, or override methods. Extract the behavior to a focused collaborator, or move the extension point to an abstract class or trait if inheritance is intentional.',
                $method->name->toString(),
                $this->classLikeNameResolver->resolve($classLike, $scope),
            )
        )
            ->identifier('customRules.nonPublicMethod')
            ->line($method->getStartLine())
            ->build();
    }
}
