<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestAssertion;

use function count;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use Toolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;

/**
 * Detects PHPUnit exception expectations aimed at types that only broken code produces.
 */
final class ExpectExceptionCallInspector
{
    /** @readonly */
    private CallMethodNameResolver $methodNameResolver;

    /** @readonly */
    private CallArgumentResolver $argumentResolver;

    /** @readonly */
    private ClassStringExpressionResolver $classStringResolver;

    /** @readonly */
    private PhpUnitCallTargetMatcher $targetMatcher;

    /** @readonly */
    private BrokenCodeExceptionClassifier $classifier;

    /** @readonly */
    private NoBrokenCodeExpectationErrorBuilder $errorBuilder;

    /**
     * Creates an inspector from call parsing, classification, and error-building collaborators.
     */
    public function __construct(
        ?CallMethodNameResolver $methodNameResolver = null,
        ?CallArgumentResolver $argumentResolver = null,
        ?ClassStringExpressionResolver $classStringResolver = null,
        ?PhpUnitCallTargetMatcher $targetMatcher = null,
        ?BrokenCodeExceptionClassifier $classifier = null,
        ?NoBrokenCodeExpectationErrorBuilder $errorBuilder = null,
    ) {
        $this->methodNameResolver = $methodNameResolver ?? new CallMethodNameResolver();
        $this->argumentResolver = $argumentResolver ?? new CallArgumentResolver();
        $this->classStringResolver = $classStringResolver ?? new ClassStringExpressionResolver();
        $this->targetMatcher = $targetMatcher ?? new PhpUnitCallTargetMatcher();
        $this->classifier = $classifier ?? new BrokenCodeExceptionClassifier();
        $this->errorBuilder = $errorBuilder ?? new NoBrokenCodeExpectationErrorBuilder();
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
                $scope,
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
                $scope,
            );
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errorsForCall(?string $methodName, ?\PhpParser\Node\Expr $firstArg, int $line, Scope $scope): array
    {
        if ($methodName !== 'expectException' && $methodName !== 'expectExceptionObject') {
            return [];
        }

        if ($firstArg === null) {
            return [];
        }

        $className = $methodName === 'expectException'
            ? $this->classStringResolver->resolve($firstArg, $scope)
            : $this->expectedObjectClassName($firstArg, $scope);
        if ($className === null) {
            return [];
        }

        $reason = $this->classifier->reason($className);
        if ($reason === null) {
            return [];
        }

        return [$this->errorBuilder->build($methodName, $className, $reason, $line)];
    }

    /**
     * Returns the single statically known class of an expected exception instance.
     */
    public function expectedObjectClassName(\PhpParser\Node\Expr $expression, Scope $scope): ?string
    {
        $classNames = $scope->getType($expression)->getObjectClassNames();

        return count($classNames) === 1 ? $classNames[0] : null;
    }
}
