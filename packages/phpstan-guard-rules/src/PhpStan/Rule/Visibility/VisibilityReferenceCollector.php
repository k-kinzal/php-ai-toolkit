<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_merge;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
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
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * Returns every declaration reference written by one node.
     *
     * @param Node $node
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $references = $this->fromNode($node, $scope);

        return $references === [] ? null : $references;
    }

    /**
     * Returns every declaration reference written by one node.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromNode(Node $node, Scope $scope): array
    {
        if ($node instanceof FunctionLike) {
            return $this->fromType($node->getReturnType(), 'return type', $scope);
        }

        if ($node instanceof Expr) {
            return $this->fromExpression($node, $scope);
        }

        if ($node instanceof ClassLike) {
            return $this->fromSupertypes($node, $scope);
        }

        if ($node instanceof TraitUse) {
            return $this->fromNames($node->traits, 'trait use', $scope);
        }

        if ($node instanceof Catch_) {
            return $this->fromNames($node->types, 'catch type', $scope);
        }

        if ($node instanceof Attribute) {
            return $this->fromNames([$node->name], 'attribute', $scope);
        }

        if ($node instanceof Param) {
            return $this->fromType($node->type, 'parameter type', $scope);
        }

        return $node instanceof Property
            ? $this->fromType($node->type, 'property type', $scope)
            : [];
    }

    /**
     * Returns the declaration reference written by one expression.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromExpression(Expr $node, Scope $scope): array
    {
        if ($node instanceof New_) {
            return $this->reference($node->class, '__construct', 'instantiation', $scope);
        }

        if ($node instanceof StaticCall) {
            return $this->reference($node->class, $this->memberName($node->name), 'static call', $scope);
        }

        if ($node instanceof StaticPropertyFetch) {
            return $this->reference($node->class, $this->memberName($node->name), 'static property access', $scope);
        }

        if ($node instanceof ClassConstFetch) {
            $member = $this->memberName($node->name);

            return $member !== null && strtolower($member) === 'class'
                ? $this->reference($node->class, null, 'class name reference', $scope)
                : $this->reference($node->class, $member, 'constant access', $scope);
        }

        return $node instanceof Instanceof_
            ? $this->reference($node->class, null, 'instanceof check', $scope)
            : [];
    }

    /**
     * Returns the parent and interface references of a class-like.
     *
     * @return list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int}>
     */
    public function fromSupertypes(ClassLike $node, Scope $scope): array
    {
        $names = [];
        if ($node instanceof Class_) {
            if ($node->extends !== null) {
                $names[] = $node->extends;
            }

            $names = array_merge($names, $node->implements);
        }

        if ($node instanceof Interface_) {
            $names = array_merge($names, $node->extends);
        }

        if ($node instanceof Enum_) {
            $names = array_merge($names, $node->implements);
        }

        return $this->fromNames($names, 'inheritance', $scope);
    }

    /**
     * Returns one reference per written class name.
     *
     * @param array<Name> $names
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
    public function fromType(?Node $typeNode, string $kind, Scope $scope): array
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
    public function reference(Node $classNode, ?string $memberName, string $kind, Scope $scope): array
    {
        if (!$classNode instanceof Name || $this->typeNameReader->isRelative($classNode)) {
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
    public function memberName(Node $nameNode): ?string
    {
        return $nameNode instanceof Identifier ? $nameNode->toString() : null;
    }
}
