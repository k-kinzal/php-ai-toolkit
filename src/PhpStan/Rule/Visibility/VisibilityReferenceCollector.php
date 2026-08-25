<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_merge;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use function strtolower;

/**
 * Records every written class-like name PHPStan analyses.
 *
 * @implements Collector<\PhpParser\Node, list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>>
 */
final class VisibilityReferenceCollector implements Collector
{
    /** @readonly */
    private TypeNameReader $typeNameReader;

    /**
     * Creates the collector from native type-name reading.
     */
    public function __construct(?TypeNameReader $typeNameReader = null)
    {
        $this->typeNameReader = $typeNameReader ?? new TypeNameReader();
    }

    /**
     * @return class-string<\PhpParser\Node>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
    }

    /**
     * Returns every declaration reference written by one node.
     *
     * @param \PhpParser\Node $node
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>|null
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): ?array
    {
        $references = $this->fromNode($node, $scope);

        return $references === [] ? null : $references;
    }

    /**
     * Returns every declaration reference written by one node.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node instanceof \PhpParser\Node\FunctionLike) {
            return $this->fromType($node->getReturnType(), 'return type', $scope);
        }

        if ($node instanceof \PhpParser\Node\Expr) {
            return $this->fromExpression($node, $scope);
        }

        if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
            return $this->fromSupertypes($node, $scope);
        }

        if ($node instanceof \PhpParser\Node\Stmt\TraitUse) {
            return $this->fromNames($node->traits, 'trait use', $scope);
        }

        if ($node instanceof \PhpParser\Node\Stmt\Catch_) {
            return $this->fromNames($node->types, 'catch type', $scope);
        }

        if ($node instanceof \PhpParser\Node\Attribute) {
            return $this->fromNames([$node->name], 'attribute', $scope);
        }

        if ($node instanceof \PhpParser\Node\Param) {
            return $this->fromType($node->type, 'parameter type', $scope);
        }

        return $node instanceof \PhpParser\Node\Stmt\Property
            ? $this->fromType($node->type, 'property type', $scope)
            : [];
    }

    /**
     * Returns the declaration reference written by one expression.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromExpression(\PhpParser\Node\Expr $node, Scope $scope): array
    {
        if ($node instanceof \PhpParser\Node\Expr\New_) {
            return $this->reference($node->class, '__construct', 'instantiation', $scope);
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            return $this->reference($node->class, $this->memberName($node->name), 'static call', $scope);
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticPropertyFetch) {
            return $this->reference($node->class, $this->memberName($node->name), 'static property access', $scope);
        }

        if ($node instanceof \PhpParser\Node\Expr\ClassConstFetch) {
            $member = $this->memberName($node->name);

            return $member !== null && strtolower($member) === 'class'
                ? $this->reference($node->class, null, 'class name reference', $scope)
                : $this->reference($node->class, $member, 'constant access', $scope);
        }

        return $node instanceof \PhpParser\Node\Expr\Instanceof_
            ? $this->reference($node->class, null, 'instanceof check', $scope)
            : [];
    }

    /**
     * Returns the parent and interface references of a class-like.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromSupertypes(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): array
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

        return $this->fromNames($names, 'inheritance', $scope);
    }

    /**
     * Returns one reference per written class name.
     *
     * @param array<\PhpParser\Node\Name> $names
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromNames(array $names, string $kind, Scope $scope): array
    {
        $references = [];
        foreach ($names as $name) {
            $references = array_merge($references, $this->reference($name, null, $kind, $scope));
        }

        return $references;
    }

    /**
     * Returns one reference per class name written in a native type.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromType(?\PhpParser\Node $typeNode, string $kind, Scope $scope): array
    {
        $references = [];
        foreach ($this->typeNameReader->namesIn($typeNode) as $name) {
            $references = array_merge($references, $this->reference($name, null, $kind, $scope));
        }

        return $references;
    }

    /**
     * Returns one reference for a named class position.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function reference(\PhpParser\Node $classNode, ?string $memberName, string $kind, Scope $scope): array
    {
        if (!$classNode instanceof \PhpParser\Node\Name || $this->typeNameReader->isRelative($classNode)) {
            return [];
        }

        return [[
            'className' => $scope->resolveName($classNode),
            'memberName' => $memberName,
            'kind' => $kind,
            'namespace' => $scope->getNamespace() ?? '',
            'line' => $classNode->getStartLine(),
        ]];
    }

    /**
     * Returns a literal member name, or null for a computed name.
     */
    public function memberName(\PhpParser\Node $nameNode): ?string
    {
        return $nameNode instanceof \PhpParser\Node\Identifier ? $nameNode->toString() : null;
    }
}
