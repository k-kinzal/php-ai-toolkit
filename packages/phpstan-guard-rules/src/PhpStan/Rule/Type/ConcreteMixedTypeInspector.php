<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Type;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;

/**
 * Finds explicit, non-template mixed inside a resolved PHPStan type.
 *
 * @visibility namespace
 */
final class ConcreteMixedTypeInspector
{
    /**
     * Reports whether a resolved declaration contains concrete mixed.
     */
    public function contains(Type $type): bool
    {
        $contains = false;
        TypeTraverser::map(
            $type,
            static function (Type $inner, callable $traverse) use (&$contains): Type {
                if ($inner instanceof TemplateType) {
                    return $inner;
                }

                if ($inner instanceof MixedType && $inner->isExplicitMixed()) {
                    $contains = true;
                }

                return $traverse($inner);
            }
        );

        return $contains;
    }

    /**
     * Reports whether a resolved declaration contains concrete mixed,
     * including mixed inferred from an omitted type.
     */
    public function containsIncludingImplicit(Type $type): bool
    {
        $contains = false;
        TypeTraverser::map(
            $type,
            static function (Type $inner, callable $traverse) use (&$contains): Type {
                if ($inner instanceof TemplateType) {
                    return $inner;
                }

                if ($inner instanceof MixedType) {
                    $contains = true;
                }

                return $traverse($inner);
            }
        );

        return $contains;
    }

    /**
     * Describes the complete declaration that contains mixed.
     */
    public function describe(Type $type): string
    {
        return $type->describe(VerbosityLevel::typeOnly());
    }
}
