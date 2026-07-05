<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;

/**
 * Detects prohibited PHPUnit mock API calls in test classes.
 */
final class PhpUnitMockApiCallInspector
{
    private readonly CallMethodNameResolver $methodNameResolver;

    private readonly CallArgumentResolver $argumentResolver;

    private readonly ClassStringExpressionResolver $classStringResolver;

    private readonly PhpUnitCallTargetMatcher $targetMatcher;

    private readonly PhpUnitMockApiMethodPolicy $methodPolicy;

    private readonly PhpUnitMockApiErrorBuilder $errorBuilder;

    /**
     * Creates an inspector from call parsing, reflection, and policy collaborators.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        ?CallMethodNameResolver $methodNameResolver = null,
        ?CallArgumentResolver $argumentResolver = null,
        ?ClassStringExpressionResolver $classStringResolver = null,
        ?PhpUnitCallTargetMatcher $targetMatcher = null,
        ?PhpUnitMockApiMethodPolicy $methodPolicy = null,
        ?PhpUnitMockApiErrorBuilder $errorBuilder = null,
    ) {
        $this->methodNameResolver = $methodNameResolver ?? new CallMethodNameResolver();
        $this->argumentResolver = $argumentResolver ?? new CallArgumentResolver();
        $this->classStringResolver = $classStringResolver ?? new ClassStringExpressionResolver();
        $this->targetMatcher = $targetMatcher ?? new PhpUnitCallTargetMatcher();
        $this->methodPolicy = $methodPolicy ?? new PhpUnitMockApiMethodPolicy();
        $this->errorBuilder = $errorBuilder ?? new PhpUnitMockApiErrorBuilder();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Expr $node, Scope $scope): array
    {
        if ($node instanceof \PhpParser\Node\Expr\MethodCall) {
            if (!$this->targetMatcher->isThisMethodCall($node)) {
                return [];
            }

            return $this->errorsForCall(
                $this->methodNameResolver->resolve($node->name),
                $this->argumentResolver->firstValue($node->args),
                $node->getStartLine(),
                $scope
            );
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            if (!$this->targetMatcher->isStaticCallOnCurrentTestClass($node, $scope)) {
                return [];
            }

            return $this->errorsForCall(
                $this->methodNameResolver->resolve($node->name),
                $this->argumentResolver->firstValue($node->args),
                $node->getStartLine(),
                $scope
            );
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errorsForCall(?string $methodName, ?\PhpParser\Node\Expr $firstArg, int $line, Scope $scope): array
    {
        if ($methodName === null) {
            return [];
        }

        if ($this->methodPolicy->isAlwaysProhibited($methodName)) {
            return [$this->errorBuilder->prohibitedApi($methodName, $line)];
        }

        if (!$this->methodPolicy->requiresInterfaceTarget($methodName)) {
            return [];
        }

        if ($firstArg === null) {
            return [$this->errorBuilder->requiresLiteralInterface($methodName, $line)];
        }

        $targetTypeName = $this->classStringResolver->resolve($firstArg, $scope);
        if ($targetTypeName === null) {
            return [$this->errorBuilder->requiresLiteralInterface($methodName, $line)];
        }

        if (!$this->reflectionProvider->hasClass($targetTypeName)) {
            return [$this->errorBuilder->requiresInterface($methodName, $targetTypeName, $line)];
        }

        if ($this->reflectionProvider->getClass($targetTypeName)->isInterface()) {
            return [];
        }

        return [$this->errorBuilder->requiresInterface($methodName, $targetTypeName, $line)];
    }
}
