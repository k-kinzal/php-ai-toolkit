<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;

use function sprintf;

/**
 * Checks a single access against the @visibility scope of what it reaches.
 *
 * A member is never more visible than the class-like that declares it, so every
 * member check falls back to the class check once the member itself allows the caller.
 */
final class VisibilityAccessChecker
{
    /** @readonly */
    private VisibilityScopeResolver $scopeResolver;

    /** @readonly */
    private NamespaceLineage $lineage;

    /** @readonly */
    private VisibilityErrorBuilder $errorBuilder;

    /**
     * Creates the checker from scope resolution, namespace ancestry, and error building.
     */
    public function __construct(
        ?VisibilityScopeResolver $scopeResolver = null,
        ?NamespaceLineage $lineage = null,
        ?VisibilityErrorBuilder $errorBuilder = null,
    ) {
        $this->scopeResolver = $scopeResolver ?? new VisibilityScopeResolver();
        $this->lineage = $lineage ?? new NamespaceLineage();
        $this->errorBuilder = $errorBuilder ?? new VisibilityErrorBuilder();
    }

    /**
     * Checks a reference to the class-like itself.
     */
    public function checkClass(ClassReflection $class, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        $docComment = $class->getNativeReflection()->getDocComment();
        $subject = sprintf('%s %s', $this->kindOf($class), $class->getName());

        return $this->checkDocComment($docComment === false ? null : $docComment, $class, $subject, $callerNamespace, $line);
    }

    /**
     * Checks a call to a method, including the constructor behind a "new" expression.
     */
    public function checkMethod(ClassReflection $class, string $methodName, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$class->hasNativeMethod($methodName)) {
            return $this->checkClass($class, $callerNamespace, $line);
        }

        $method = $class->getNativeMethod($methodName);
        $declaringClass = $method->getDeclaringClass();
        $subject = sprintf('Call to %s::%s()', $declaringClass->getName(), $method->getName());

        return $this->checkDocComment($method->getDocComment(), $declaringClass, $subject, $callerNamespace, $line)
            ?? $this->checkClass($declaringClass, $callerNamespace, $line);
    }

    /**
     * Checks a read or write of a declared property.
     */
    public function checkProperty(ClassReflection $class, string $propertyName, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$class->hasNativeProperty($propertyName)) {
            return $this->checkClass($class, $callerNamespace, $line);
        }

        $property = $class->getNativeProperty($propertyName);
        $declaringClass = $property->getDeclaringClass();
        $subject = sprintf('Access to property %s::$%s', $declaringClass->getName(), $propertyName);

        return $this->checkDocComment($property->getDocComment(), $declaringClass, $subject, $callerNamespace, $line)
            ?? $this->checkClass($declaringClass, $callerNamespace, $line);
    }

    /**
     * Checks a read of a class constant or enum case.
     */
    public function checkConstant(ClassReflection $class, string $constantName, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$class->hasConstant($constantName)) {
            return $this->checkClass($class, $callerNamespace, $line);
        }

        $constant = $class->getConstant($constantName);
        $declaringClass = $constant->getDeclaringClass();
        $subject = sprintf('Access to constant %s::%s', $declaringClass->getName(), $constantName);

        return $this->checkDocComment($constant->getDocComment(), $declaringClass, $subject, $callerNamespace, $line)
            ?? $this->checkClass($declaringClass, $callerNamespace, $line);
    }

    /**
     * Checks one PHPDoc comment of a declaration in the given class-like against the caller.
     */
    public function checkDocComment(
        ?string $docComment,
        ClassReflection $declaringClass,
        string $subject,
        string $callerNamespace,
        int $line,
    ): ?IdentifierRuleError {
        $declaringNamespace = $this->lineage->of($declaringClass->getName());
        $scope = $this->scopeResolver->resolve($docComment, $declaringNamespace);
        if ($scope->permits($callerNamespace)) {
            return null;
        }

        return $this->errorBuilder->outOfScope($subject, $scope, $callerNamespace, $declaringNamespace, $line);
    }

    /**
     * Returns the declaration keyword of a class-like, capitalised for a sentence subject.
     */
    public function kindOf(ClassReflection $class): string
    {
        if ($class->isInterface()) {
            return 'Interface';
        }

        if ($class->isTrait()) {
            return 'Trait';
        }

        return $class->isEnum() ? 'Enum' : 'Class';
    }
}
