<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Resolves a human-readable label for a class-like declaration kind.
 */
final class ClassLikeKindLabel
{
    /**
     * Returns class, interface, trait, or enum for the node.
     */
    public function label(\PhpParser\Node\Stmt\ClassLike $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            return 'interface';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            return 'trait';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Enum_) {
            return 'enum';
        }

        return 'class';
    }
}
