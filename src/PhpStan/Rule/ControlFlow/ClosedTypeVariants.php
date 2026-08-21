<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use function count;

use PHPStan\Type\Type;

/**
 * Lists every value a closed type can hold.
 *
 * A type is closed when PHP itself bounds the values it admits: an enum admits its cases,
 * a bool admits true and false, null admits null, and a union of literals admits those
 * literals. Anything wider — a plain string, an object, a class that may be extended
 * anywhere in the program — has no such list and is left alone.
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
}
