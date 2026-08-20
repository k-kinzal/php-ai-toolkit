<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function array_merge;
use function in_array;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;

use function strtolower;

/**
 * Resolves the class-likes that a parser node refers to.
 */
final class ReferencedClassResolver
{
    /**
     * Returns the class-likes a class position refers to, from a name or from an inferred type.
     *
     * self, static, and parent are left alone: they can only name the class the caller is
     * already inside or one it inherits from, and inheritance is checked at the declaration.
     *
     * @return list<ClassReflection>
     */
    public function fromNode(\PhpParser\Node $classNode, Scope $scope): array
    {
        if ($classNode instanceof \PhpParser\Node\Name) {
            if (in_array(strtolower($classNode->toString()), ['self', 'static', 'parent'], true)) {
                return [];
            }

            return $scope->resolveTypeByName($classNode)->getObjectClassReflections();
        }

        if ($classNode instanceof \PhpParser\Node\Expr) {
            return $scope->getType($classNode)->getObjectClassReflections();
        }

        return [];
    }

    /**
     * Returns every class name written inside a type declaration, unwrapping nullable, union, and intersection types.
     *
     * @return list<\PhpParser\Node\Name>
     */
    public function namesIn(?\PhpParser\Node $typeNode): array
    {
        if ($typeNode instanceof \PhpParser\Node\Name) {
            return [$typeNode];
        }

        if ($typeNode instanceof \PhpParser\Node\NullableType) {
            return $this->namesIn($typeNode->type);
        }

        if (!$typeNode instanceof \PhpParser\Node\UnionType && !$typeNode instanceof \PhpParser\Node\IntersectionType) {
            return [];
        }

        $names = [];
        foreach ($typeNode->types as $memberType) {
            $names = array_merge($names, $this->namesIn($memberType));
        }

        return $names;
    }
}
