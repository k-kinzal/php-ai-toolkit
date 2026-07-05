<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\ObjectType;

/**
 * Detects PHPUnit assertInstanceOf() calls guaranteed by static types.
 */
final class AssertInstanceOfRedundancyInspector
{
    private readonly CallMethodNameResolver $methodNameResolver;

    private readonly CallArgumentResolver $argumentResolver;

    private readonly ClassStringExpressionResolver $classStringResolver;

    private readonly PhpUnitCallTargetMatcher $targetMatcher;

    private readonly NoRedundantAssertInstanceOfErrorBuilder $errorBuilder;

    /**
     * Creates an inspector from call parsing and error-building collaborators.
     */
    public function __construct(
        ?CallMethodNameResolver $methodNameResolver = null,
        ?CallArgumentResolver $argumentResolver = null,
        ?ClassStringExpressionResolver $classStringResolver = null,
        ?PhpUnitCallTargetMatcher $targetMatcher = null,
        ?NoRedundantAssertInstanceOfErrorBuilder $errorBuilder = null,
    ) {
        $this->methodNameResolver = $methodNameResolver ?? new CallMethodNameResolver();
        $this->argumentResolver = $argumentResolver ?? new CallArgumentResolver();
        $this->classStringResolver = $classStringResolver ?? new ClassStringExpressionResolver();
        $this->targetMatcher = $targetMatcher ?? new PhpUnitCallTargetMatcher();
        $this->errorBuilder = $errorBuilder ?? new NoRedundantAssertInstanceOfErrorBuilder();
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
                $node->args,
                $node->getStartLine(),
                $scope,
            );
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            if (!$this->targetMatcher->isStaticCallOnPhpUnitAssert($node, $scope)) {
                return [];
            }

            return $this->errorsForCall(
                $this->methodNameResolver->resolve($node->name),
                $node->args,
                $node->getStartLine(),
                $scope,
            );
        }

        return [];
    }

    /**
     * @param array<array-key, \PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     * @return list<IdentifierRuleError>
     */
    public function errorsForCall(?string $methodName, array $args, int $line, Scope $scope): array
    {
        if ($methodName !== 'assertInstanceOf') {
            return [];
        }

        $expectedExpression = $this->argumentResolver->valueAt($args, 0);
        $actualExpression = $this->argumentResolver->valueAt($args, 1);
        if ($expectedExpression === null || $actualExpression === null) {
            return [];
        }

        $expectedTypeName = $this->classStringResolver->resolve($expectedExpression, $scope);
        if ($expectedTypeName === null) {
            return [];
        }

        $actualType = $scope->getType($actualExpression);
        $actualTypeNames = $actualType->getObjectClassNames();
        if (count($actualTypeNames) !== 1) {
            return [];
        }

        if (!(new ObjectType($expectedTypeName))->isSuperTypeOf($actualType)->yes()) {
            return [];
        }

        return [$this->errorBuilder->build($actualTypeNames[0], $expectedTypeName, $line)];
    }
}
