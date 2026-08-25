<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Executor;

use function class_exists;
use function get_class;
use function interface_exists;
use function is_a;

use ReflectionClass;
use Throwable;

/**
 * Decides whether a thrown exception is the one an example documented.
 *
 * A documented class name is matched fully qualified when it can be resolved,
 * and by short name when it cannot, so an example may name an exception the way
 * the prose around it does.
 *
 * @visibility parent
 */
final class ExceptionMatcher
{
    /**
     * Reports whether the thrown exception matches the documented class name.
     */
    public function matches(Throwable $exception, string $expectedClass): bool
    {
        if (is_a($exception, $expectedClass)) {
            return true;
        }

        if (class_exists($expectedClass) || interface_exists($expectedClass)) {
            return false;
        }

        return (new ReflectionClass($exception))->getShortName() === $expectedClass || get_class($exception) === $expectedClass;
    }
}
