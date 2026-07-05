<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use function is_a;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

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

        try {
            return (new ObjectType($expectedTypeName))->isSuperTypeOf($actualType)->yes();
        } catch (Throwable $exception) {
            if (!is_a($exception, 'PHPStan\Reflection\MissingStaticAccessorInstanceException')) {
                throw $exception;
            }

            return false;
        }
    }
}
