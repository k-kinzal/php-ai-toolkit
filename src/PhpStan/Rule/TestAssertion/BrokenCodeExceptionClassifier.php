<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestAssertion;

use PHPStan\Type\ObjectType;

use function sprintf;
use function strtolower;

/**
 * Classifies expected exception types that only surface when the code under test is broken.
 *
 * Throwable is every failure at once, the LogicException family is a programmer
 * error, and the Error family is an engine failure. None of them is behavior a
 * caller can rely on, so none of them is worth expecting in a test.
 */
final class BrokenCodeExceptionClassifier
{
    /**
     * Returns why the exception type must not be expected, or null when expecting it states real behavior.
     */
    public function reason(string $className): ?string
    {
        if (strtolower($className) === 'throwable') {
            return sprintf('%s matches every failure, so a passing test says nothing about what the code under test did', $className);
        }

        if ((new ObjectType('LogicException'))->isSuperTypeOf(new ObjectType($className))->yes()) {
            return sprintf('%s is a programmer error (LogicException family) that only occurs while the code under test is broken', $className);
        }

        if ((new ObjectType('Error'))->isSuperTypeOf(new ObjectType($className))->yes()) {
            return sprintf('%s is an engine failure (Error family) that only occurs while the code under test is broken', $className);
        }

        return null;
    }
}
