<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;

use function strtolower;

/**
 * Inspects the expressions that reach a declaration: calls, instantiation, and member access.
 */
final class VisibilityUsageInspector
{
    /** @readonly */
    private VisibilityAccessChecker $accessChecker;

    /** @readonly */
    private ReferencedClassResolver $referencedClassResolver;

    /**
     * Creates the inspector from access checking and class reference resolution.
     */
    public function __construct(
        ?VisibilityAccessChecker $accessChecker = null,
        ?ReferencedClassResolver $referencedClassResolver = null,
    ) {
        $this->accessChecker = $accessChecker ?? new VisibilityAccessChecker();
        $this->referencedClassResolver = $referencedClassResolver ?? new ReferencedClassResolver();
    }

    /**
     * Returns the visibility errors of one expression.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Expr $node, Scope $scope, string $callerNamespace): array
    {
        if ($node instanceof \PhpParser\Node\Expr\New_) {
            return $this->methodErrors($this->referencedClassResolver->fromNode($node->class, $scope), '__construct', $callerNamespace, $node->getStartLine());
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            return $this->methodErrors($this->referencedClassResolver->fromNode($node->class, $scope), $this->nameOf($node->name), $callerNamespace, $node->getStartLine());
        }

        if ($node instanceof \PhpParser\Node\Expr\MethodCall || $node instanceof \PhpParser\Node\Expr\NullsafeMethodCall) {
            return $this->methodErrors($this->referencedClassResolver->fromNode($node->var, $scope), $this->nameOf($node->name), $callerNamespace, $node->getStartLine());
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticPropertyFetch) {
            return $this->propertyErrors($this->referencedClassResolver->fromNode($node->class, $scope), $this->nameOf($node->name), $callerNamespace, $node->getStartLine());
        }

        if ($node instanceof \PhpParser\Node\Expr\PropertyFetch || $node instanceof \PhpParser\Node\Expr\NullsafePropertyFetch) {
            return $this->propertyErrors($this->referencedClassResolver->fromNode($node->var, $scope), $this->nameOf($node->name), $callerNamespace, $node->getStartLine());
        }

        if ($node instanceof \PhpParser\Node\Expr\ClassConstFetch) {
            return $this->constantErrors($this->referencedClassResolver->fromNode($node->class, $scope), $this->nameOf($node->name), $callerNamespace, $node->getStartLine());
        }

        if ($node instanceof \PhpParser\Node\Expr\Instanceof_) {
            return $this->classErrors($this->referencedClassResolver->fromNode($node->class, $scope), $callerNamespace, $node->getStartLine());
        }

        return [];
    }

    /**
     * Returns the member name a node spells out, or null when it is computed at runtime.
     */
    public function nameOf(\PhpParser\Node $nameNode): ?string
    {
        if ($nameNode instanceof \PhpParser\Node\Identifier) {
            return $nameNode->toString();
        }

        return null;
    }

    /**
     * Returns the first method visibility error among the given class-likes.
     *
     * @param list<ClassReflection> $classes
     * @return list<IdentifierRuleError>
     */
    public function methodErrors(array $classes, ?string $methodName, string $callerNamespace, int $line): array
    {
        if ($methodName === null) {
            return $this->classErrors($classes, $callerNamespace, $line);
        }

        foreach ($classes as $class) {
            $error = $this->accessChecker->checkMethod($class, $methodName, $callerNamespace, $line);
            if ($error !== null) {
                return [$error];
            }
        }

        return [];
    }

    /**
     * Returns the first property visibility error among the given class-likes.
     *
     * @param list<ClassReflection> $classes
     * @return list<IdentifierRuleError>
     */
    public function propertyErrors(array $classes, ?string $propertyName, string $callerNamespace, int $line): array
    {
        if ($propertyName === null) {
            return $this->classErrors($classes, $callerNamespace, $line);
        }

        foreach ($classes as $class) {
            $error = $this->accessChecker->checkProperty($class, $propertyName, $callerNamespace, $line);
            if ($error !== null) {
                return [$error];
            }
        }

        return [];
    }

    /**
     * Returns the first constant visibility error among the given class-likes.
     *
     * @param list<ClassReflection> $classes
     * @return list<IdentifierRuleError>
     */
    public function constantErrors(array $classes, ?string $constantName, string $callerNamespace, int $line): array
    {
        if ($constantName === null || strtolower($constantName) === 'class') {
            return $this->classErrors($classes, $callerNamespace, $line);
        }

        foreach ($classes as $class) {
            $error = $this->accessChecker->checkConstant($class, $constantName, $callerNamespace, $line);
            if ($error !== null) {
                return [$error];
            }
        }

        return [];
    }

    /**
     * Returns the first class visibility error among the given class-likes.
     *
     * @param list<ClassReflection> $classes
     * @return list<IdentifierRuleError>
     */
    public function classErrors(array $classes, string $callerNamespace, int $line): array
    {
        foreach ($classes as $class) {
            $error = $this->accessChecker->checkClass($class, $callerNamespace, $line);
            if ($error !== null) {
                return [$error];
            }
        }

        return [];
    }
}
