<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Reference;

use function array_merge;
use function strtolower;

/**
 * Collects every place one namespace group names another declaration.
 *
 * Only written names are collected. A standalone checker infers no types, and it
 * does not need to: a scoped declaration has to be named somewhere to be reached,
 * which is the same thing a module system checks.
 *
 * @visibility parent
 */
final class ReferenceCollector
{
    /** @readonly */
    private TypeNameReader $typeNameReader;

    /**
     * Creates the collector from type reading.
     */
    public function __construct(?TypeNameReader $typeNameReader = null)
    {
        $this->typeNameReader = $typeNameReader ?? new TypeNameReader();
    }

    /**
     * Returns every reference written in the given nodes.
     *
     * @param list<\PhpParser\Node> $nodes
     * @return list<Reference>
     */
    public function collect(array $nodes, string $namespace, string $path): array
    {
        $references = [];
        foreach ($nodes as $node) {
            $references = array_merge($references, $this->fromNode($node, $namespace, $path));
        }

        return $references;
    }

    /**
     * Returns the references one node writes.
     *
     * @return list<Reference>
     */
    public function fromNode(\PhpParser\Node $node, string $namespace, string $path): array
    {
        if ($node instanceof \PhpParser\Node\FunctionLike) {
            return $this->fromType($node->getReturnType(), 'return type', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Expr) {
            return $this->fromExpression($node, $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
            return $this->fromSupertypes($node, $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Stmt\TraitUse) {
            return $this->fromNames($node->traits, 'trait use', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Stmt\Catch_) {
            return $this->fromNames($node->types, 'catch type', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Attribute) {
            return $this->fromNames([$node->name], 'attribute', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Param) {
            return $this->fromType($node->type, 'parameter type', $namespace, $path);
        }

        return $node instanceof \PhpParser\Node\Stmt\Property
            ? $this->fromType($node->type, 'property type', $namespace, $path)
            : [];
    }

    /**
     * Returns the references one expression writes.
     *
     * @return list<Reference>
     */
    public function fromExpression(\PhpParser\Node\Expr $node, string $namespace, string $path): array
    {
        if ($node instanceof \PhpParser\Node\Expr\New_) {
            return $this->reference($node->class, '__construct', 'instantiation', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticCall) {
            return $this->reference($node->class, $this->memberName($node->name), 'static call', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Expr\StaticPropertyFetch) {
            return $this->reference($node->class, $this->memberName($node->name), 'static property access', $namespace, $path);
        }

        if ($node instanceof \PhpParser\Node\Expr\ClassConstFetch) {
            $member = $this->memberName($node->name);

            return $member !== null && strtolower($member) === 'class'
                ? $this->reference($node->class, null, 'class name reference', $namespace, $path)
                : $this->reference($node->class, $member, 'constant access', $namespace, $path);
        }

        return $node instanceof \PhpParser\Node\Expr\Instanceof_
            ? $this->reference($node->class, null, 'instanceof check', $namespace, $path)
            : [];
    }

    /**
     * Returns the references a class-like header writes.
     *
     * @return list<Reference>
     */
    public function fromSupertypes(\PhpParser\Node\Stmt\ClassLike $node, string $namespace, string $path): array
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

        return $this->fromNames($names, 'inheritance', $namespace, $path);
    }

    /**
     * Returns one reference per written class name.
     *
     * @param array<\PhpParser\Node\Name> $names
     * @return list<Reference>
     */
    public function fromNames(array $names, string $kind, string $namespace, string $path): array
    {
        $references = [];
        foreach ($names as $name) {
            $references = array_merge($references, $this->reference($name, null, $kind, $namespace, $path));
        }

        return $references;
    }

    /**
     * Returns one reference per class name written in a type declaration.
     *
     * @return list<Reference>
     */
    public function fromType(?\PhpParser\Node $typeNode, string $kind, string $namespace, string $path): array
    {
        $references = [];
        foreach ($this->typeNameReader->namesIn($typeNode) as $name) {
            $references = array_merge($references, $this->reference($name, null, $kind, $namespace, $path));
        }

        return $references;
    }

    /**
     * Returns one reference when the class position names a type, and nothing otherwise.
     *
     * @return list<Reference>
     */
    public function reference(\PhpParser\Node $classNode, ?string $memberName, string $kind, string $namespace, string $path): array
    {
        if (!$classNode instanceof \PhpParser\Node\Name || $this->typeNameReader->isRelative($classNode)) {
            return [];
        }

        return [new Reference($classNode->toString(), $memberName, $kind, $namespace, $path, $classNode->getStartLine())];
    }

    /**
     * Returns the member name a node spells out, or null when it is computed at runtime.
     */
    public function memberName(\PhpParser\Node $nameNode): ?string
    {
        return $nameNode instanceof \PhpParser\Node\Identifier ? $nameNode->toString() : null;
    }
}
