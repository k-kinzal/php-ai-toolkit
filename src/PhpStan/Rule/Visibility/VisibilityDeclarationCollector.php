<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_merge;

use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use function sprintf;

use Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;

/**
 * Records class-like and member declarations from PHPStan's analysed files.
 *
 * @implements Collector<\PhpParser\Node\Stmt\ClassLike, array{
 *     class: array{className: string, memberName: null, symbol: string, kind: string, namespace: string, docComment: ?string, line: int},
 *     parents: list<string>,
 *     members: list<array{className: string, memberName: string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int}>
 * }>
 */
final class VisibilityDeclarationCollector implements Collector
{
    /** @readonly */
    private NamespaceLineage $lineage;

    /** @readonly */
    private ClassLikeKindLabel $kindLabel;

    /**
     * Creates the collector from namespace and class-like naming helpers.
     */
    public function __construct(?NamespaceLineage $lineage = null, ?ClassLikeKindLabel $kindLabel = null)
    {
        $this->lineage = $lineage ?? new NamespaceLineage();
        $this->kindLabel = $kindLabel ?? new ClassLikeKindLabel();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * Returns one class-like with every member declared directly on it.
     *
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return array{
     *     class: array{className: string, memberName: null, symbol: string, kind: string, namespace: string, docComment: ?string, line: int},
     *     parents: list<string>,
     *     members: list<array{className: string, memberName: string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int}>
     * }|null
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): ?array
    {
        if (!isset($node->namespacedName)) {
            return null;
        }

        $className = $node->namespacedName->toString();
        $namespace = $this->lineage->of($className);

        return [
            'class' => [
                'className' => $className,
                'memberName' => null,
                'symbol' => $className,
                'kind' => $this->kindLabel->label($node),
                'namespace' => $namespace,
                'docComment' => $this->docComment($node),
                'line' => $node->getStartLine(),
            ],
            'parents' => $this->supertypes($node, $scope),
            'members' => $this->members($node, $className, $namespace),
        ];
    }

    /**
     * Returns every member declared directly on one class-like.
     *
     * @return list<array{className: string, memberName: string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int}>
     */
    public function members(\PhpParser\Node\Stmt\ClassLike $node, string $className, string $namespace): array
    {
        $members = [];
        foreach ($node->getMethods() as $method) {
            $name = $method->name->toString();
            $members[] = $this->member($className, $name, sprintf('%s::%s()', $className, $name), 'method', $namespace, $method);
        }

        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $declaredProperty) {
                $name = $declaredProperty->name->toString();
                $members[] = $this->member($className, $name, sprintf('%s::$%s', $className, $name), 'property', $namespace, $property);
            }
        }

        foreach ($node->getConstants() as $classConstant) {
            foreach ($classConstant->consts as $declaredConstant) {
                $name = $declaredConstant->name->toString();
                $members[] = $this->member($className, $name, sprintf('%s::%s', $className, $name), 'constant', $namespace, $classConstant);
            }
        }

        foreach ($node->stmts as $statement) {
            if (!$statement instanceof \PhpParser\Node\Stmt\EnumCase) {
                continue;
            }

            $name = $statement->name->toString();
            $members[] = $this->member($className, $name, sprintf('%s::%s', $className, $name), 'enum case', $namespace, $statement);
        }

        return $members;
    }

    /**
     * Returns the declaration data for one member.
     *
     * @return array{className: string, memberName: string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int}
     */
    public function member(
        string $className,
        string $memberName,
        string $symbol,
        string $kind,
        string $namespace,
        \PhpParser\Node $node,
    ): array {
        return [
            'className' => $className,
            'memberName' => $memberName,
            'symbol' => $symbol,
            'kind' => $kind,
            'namespace' => $namespace,
            'docComment' => $this->docComment($node),
            'line' => $node->getStartLine(),
        ];
    }

    /**
     * Returns every parent, interface, and trait named by a class-like.
     *
     * @return list<string>
     */
    public function supertypes(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): array
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

        $supertypes = [];
        foreach ($names as $name) {
            $supertypes[] = $scope->resolveName($name);
        }

        return $supertypes;
    }

    /**
     * Returns a node's raw PHPDoc text, or null when it has none.
     */
    public function docComment(\PhpParser\Node $node): ?string
    {
        $docComment = $node->getDocComment();

        return $docComment === null ? null : $docComment->getText();
    }
}
