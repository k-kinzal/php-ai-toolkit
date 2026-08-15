<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NoRedundantAssertInstanceOf;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Compares assertInstanceOf() expected and actual static types.
 */
final class AssertInstanceOfTypeMatcher
{
    /**
     * Reports whether the actual static type is already guaranteed to satisfy the expected type.
     */
    public function matches(string $expectedTypeName, Type $actualType, string $actualTypeName): bool
    {
        if ($actualTypeName === $expectedTypeName) {
            return true;
        }

        return (new ObjectType($expectedTypeName))->isSuperTypeOf($actualType)->yes();
    }
}
