<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;

/**
 * Resolves a human-readable label for a class-like declaration kind.
 */
final class ClassLikeKindLabel
{
    /**
     * Returns class, interface, trait, or enum for the node.
     */
    public function label(ClassLike $node): string
    {
        if ($node instanceof Interface_) {
            return 'interface';
        }

        if ($node instanceof Trait_) {
            return 'trait';
        }

        if ($node instanceof Enum_) {
            return 'enum';
        }

        return 'class';
    }
}
