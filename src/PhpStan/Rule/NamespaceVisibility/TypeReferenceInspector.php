<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function array_merge;
use function array_values;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;

/**
 * Inspects the class-likes a declaration names in its own header and member signatures.
 */
final class TypeReferenceInspector
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
     * Returns the visibility errors of every class-like named by a declaration.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope, string $callerNamespace): array
    {
        $errors = [];
        foreach (array_merge($this->supertypeNames($node), $this->memberTypeNames($node)) as $name) {
            foreach ($this->referencedClassResolver->fromNode($name, $scope) as $class) {
                $error = $this->accessChecker->checkClass($class, $callerNamespace, $name->getStartLine());
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Returns the names of the parents, interfaces, and traits a declaration builds on.
     *
     * @return list<\PhpParser\Node\Name>
     */
    public function supertypeNames(\PhpParser\Node\Stmt\ClassLike $node): array
    {
        $names = [];
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            if ($node->extends !== null) {
                $names[] = $node->extends;
            }

            $names = array_merge($names, $node->implements);
        }

        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            $names = array_merge($names, $node->extends);
        }

        if ($node instanceof \PhpParser\Node\Stmt\Enum_) {
            $names = array_merge($names, $node->implements);
        }

        foreach ($node->getTraitUses() as $traitUse) {
            $names = array_merge($names, $traitUse->traits);
        }

        return array_values($names);
    }

    /**
     * Returns the names written in the parameter, return, and property types of a declaration.
     *
     * @return list<\PhpParser\Node\Name>
     */
    public function memberTypeNames(\PhpParser\Node\Stmt\ClassLike $node): array
    {
        $names = [];
        foreach ($node->getMethods() as $method) {
            foreach ($method->params as $param) {
                $names = array_merge($names, $this->referencedClassResolver->namesIn($param->type));
            }

            $names = array_merge($names, $this->referencedClassResolver->namesIn($method->returnType));
        }

        foreach ($node->getProperties() as $property) {
            $names = array_merge($names, $this->referencedClassResolver->namesIn($property->type));
        }

        return $names;
    }
}
