<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequireExhaustiveDispatch;

use function count;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Lists every value a closed type can hold.
 *
 * A type is closed when PHP itself bounds the values it admits: an enum admits its cases,
 * a bool admits true and false, null admits null, and a union of literals admits those
 * literals. Rust and Kotlin get the same list from an enum or a sealed class; PHP has no
 * sealed keyword, so a hierarchy is closed here only when the subject is written as a union
 * of the classes it can be. Anything wider — a plain string, an interface, a class that may
 * be extended anywhere in the program — has no such list and is left alone.
 */
final class ClosedTypeVariants
{
    /**
     * The largest number of values a type may admit before it is treated as open.
     *
     * Past this point the list stops being something a reader can act on, and the
     * type is almost certainly not a hand-written set of alternatives.
     */
    public const MAX_VARIANTS = 32;

    /**
     * The smallest number of classes a union must name before it stands in for a sealed hierarchy.
     *
     * One class is a single type rather than a choice between alternatives, and reading a
     * dispatch over it as a type dispatch would misread ordinary comparisons.
     */
    public const MIN_CLASS_VARIANTS = 2;

    /**
     * Returns the values a type admits, or null when it admits unboundedly many.
     *
     * @return list<Type>|null
     */
    public function values(Type $type): ?array
    {
        $finiteTypes = $type->getFiniteTypes();
        if ($finiteTypes === [] || count($finiteTypes) > self::MAX_VARIANTS) {
            return null;
        }

        return $finiteTypes;
    }

    /**
     * Returns the classes a union of object types names, or null when the type is not one.
     *
     * The union has to describe the type exactly: an intersection, a generic object, or a
     * subtracted type carries more than its class names, and reading it as a plain list of
     * alternatives would drop that.
     *
     * @return list<Type>|null
     */
    public function objects(Type $type): ?array
    {
        $classNames = $type->getObjectClassNames();
        if (count($classNames) < self::MIN_CLASS_VARIANTS || count($classNames) > self::MAX_VARIANTS) {
            return null;
        }

        $variants = [];
        foreach ($classNames as $className) {
            $variants[] = new ObjectType($className);
        }

        if (!TypeCombinator::union(...$variants)->equals($type)) {
            return null;
        }

        return $variants;
    }
}
