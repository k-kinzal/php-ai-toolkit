<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Declaration;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;

use function sprintf;

/**
 * Collects the scoped declarations of one namespace group into the index.
 *
 * @visibility parent
 */
final class DeclarationCollector
{
    /** @readonly */
    private VisibilityScopeResolver $scopeResolver;

    /** @readonly */
    private NamespaceLineage $lineage;

    /** @readonly */
    private ClassLikeKind $classLikeKind;

    /**
     * Creates the collector from scope resolution, namespace ancestry, and kind naming.
     */
    public function __construct(
        ?VisibilityScopeResolver $scopeResolver = null,
        ?NamespaceLineage $lineage = null,
        ?ClassLikeKind $classLikeKind = null,
    ) {
        $this->scopeResolver = $scopeResolver ?? new VisibilityScopeResolver();
        $this->lineage = $lineage ?? new NamespaceLineage();
        $this->classLikeKind = $classLikeKind ?? new ClassLikeKind();
    }

    /**
     * Records every class-like of the given nodes, with its members, in the index.
     *
     * @param list<\PhpParser\Node> $nodes
     */
    public function collect(array $nodes, string $path, DeclarationIndex $index): void
    {
        foreach ($nodes as $node) {
            if (!$node instanceof \PhpParser\Node\Stmt\ClassLike || !isset($node->namespacedName)) {
                continue;
            }

            $className = $node->namespacedName->toString();
            $namespace = $this->lineage->of($className);
            $index->addClass($className, $this->classLikeKind->supertypes($node), new Declaration(
                $className,
                $this->classLikeKind->label($node),
                $namespace,
                $this->scopeResolver->resolve($this->docComment($node), $namespace),
                $path,
                $node->getStartLine(),
            ));

            $this->collectMembers($node, $className, $namespace, $path, $index);
        }
    }

    /**
     * Records the scoped members of one class-like in the index.
     */
    public function collectMembers(
        \PhpParser\Node\Stmt\ClassLike $node,
        string $className,
        string $namespace,
        string $path,
        DeclarationIndex $index,
    ): void {
        foreach ($node->getMethods() as $method) {
            $name = $method->name->toString();
            $this->addMember($index, $className, $name, sprintf('%s::%s()', $className, $name), 'method', $namespace, $path, $method);
        }

        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $declaredProperty) {
                $name = $declaredProperty->name->toString();
                $this->addMember($index, $className, $name, sprintf('%s::$%s', $className, $name), 'property', $namespace, $path, $property);
            }
        }

        foreach ($node->getConstants() as $classConstant) {
            foreach ($classConstant->consts as $declaredConstant) {
                $name = $declaredConstant->name->toString();
                $this->addMember($index, $className, $name, sprintf('%s::%s', $className, $name), 'constant', $namespace, $path, $classConstant);
            }
        }

        foreach ($node->stmts as $statement) {
            if ($statement instanceof \PhpParser\Node\Stmt\EnumCase) {
                $name = $statement->name->toString();
                $this->addMember($index, $className, $name, sprintf('%s::%s', $className, $name), 'enum case', $namespace, $path, $statement);
            }
        }
    }

    /**
     * Records one member declaration read from its own PHPDoc comment.
     */
    public function addMember(
        DeclarationIndex $index,
        string $className,
        string $memberName,
        string $symbol,
        string $kind,
        string $namespace,
        string $path,
        \PhpParser\Node $node,
    ): void {
        $index->addMember($className, $memberName, new Declaration(
            $symbol,
            $kind,
            $namespace,
            $this->scopeResolver->resolve($this->docComment($node), $namespace),
            $path,
            $node->getStartLine(),
        ));
    }

    /**
     * Returns the raw PHPDoc text of a node, or null when it carries none.
     */
    public function docComment(\PhpParser\Node $node): ?string
    {
        $docComment = $node->getDocComment();

        return $docComment === null ? null : $docComment->getText();
    }
}
