<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Declaration;

use function array_merge;

/**
 * Names the declaration keyword of a class-like and the types it builds on.
 *
 * @visibility parent
 */
final class ClassLikeKind
{
    /**
     * Returns the declaration keyword of a class-like.
     */
    public function label(\PhpParser\Node\Stmt\ClassLike $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            return 'interface';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            return 'trait';
        }

        return $node instanceof \PhpParser\Node\Stmt\Enum_ ? 'enum' : 'class';
    }

    /**
     * Returns the fully qualified parents, interfaces, and traits of a class-like.
     *
     * @return list<string>
     */
    public function supertypes(\PhpParser\Node\Stmt\ClassLike $node): array
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
            $supertypes[] = $name->toString();
        }

        return $supertypes;
    }
}
