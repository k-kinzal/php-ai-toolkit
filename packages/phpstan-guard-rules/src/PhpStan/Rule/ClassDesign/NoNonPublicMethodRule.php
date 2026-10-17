<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassDesign;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Reports private methods and non-extension protected methods.
 *
 * Constructors are exempt: a private or protected constructor is how PHP
 * expresses singletons, named constructors, and other controlled-instantiation
 * designs, so restricting it is a deliberate API decision rather than a hidden
 * responsibility.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class NoNonPublicMethodRule implements Rule
{
    /** @readonly */
    private ProtectedMethodPolicy $protectedMethodPolicy;

    /** @readonly */
    private NonPublicMethodErrorBuilder $errorBuilder;

    /**
     * Creates the non-public method rule from its design collaborators.
     */
    public function __construct(
        ?ProtectedMethodPolicy $protectedMethodPolicy = null,
        ?NonPublicMethodErrorBuilder $errorBuilder = null,
    ) {
        $this->protectedMethodPolicy = $protectedMethodPolicy ?? new ProtectedMethodPolicy();
        $this->errorBuilder = $errorBuilder ?? new NonPublicMethodErrorBuilder();
    }

    /**
     * @return class-string<ClassLike>
     */
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @param ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->getMethods() as $method) {
            if ($method->name->toLowerString() === '__construct') {
                continue;
            }

            if ($method->isPrivate()) {
                $errors[] = $this->errorBuilder->privateMethod($method, $node, $scope);
                continue;
            }

            if (!$method->isProtected()) {
                continue;
            }

            if ($this->protectedMethodPolicy->allows($node, $method)) {
                continue;
            }

            $errors[] = $this->errorBuilder->protectedMethod($method, $node, $scope);
        }

        return $errors;
    }
}
