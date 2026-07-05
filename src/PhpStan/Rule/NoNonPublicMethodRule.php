<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Reports private methods and non-extension protected methods.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class NoNonPublicMethodRule implements Rule
{
    private readonly ProtectedMethodPolicy $protectedMethodPolicy;

    private readonly NonPublicMethodErrorBuilder $errorBuilder;

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
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->getMethods() as $method) {
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
