<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function array_keys;
use function array_merge;
use function class_implements;
use function class_parents;
use function get_class;
use function is_a;
use function ltrim;
use function str_contains;
use function strrpos;
use function substr;

use Throwable;

/**
 * Matches a thrown exception against the class an example expected.
 *
 * The expected class is matched fully qualified first, and by short name after
 * that, so an example may name an exception the way the surrounding prose does
 * without importing it.
 */
final class ExceptionMatcher
{
    /**
     * Reports whether the thrown exception is the one the example expected.
     */
    public function matches(Throwable $thrown, string $expected): bool
    {
        $wanted = ltrim($expected, '\\');
        if ($wanted !== '' && is_a($thrown, $wanted)) {
            return true;
        }

        foreach ($this->lineage($thrown) as $name) {
            if ($name === $wanted || $this->shortName($name) === $wanted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reports whether the thrown message carries the fragment the example expected.
     */
    public function matchesMessage(Throwable $thrown, ?string $expected): bool
    {
        return $expected === null || $expected === '' || str_contains($thrown->getMessage(), $expected);
    }

    /**
     * Returns the class of the exception together with its parents and interfaces.
     *
     * @return list<string>
     */
    public function lineage(Throwable $thrown): array
    {
        return array_merge(
            [get_class($thrown)],
            array_keys(class_parents($thrown)),
            array_keys(class_implements($thrown)),
        );
    }

    /**
     * Returns the unqualified part of a class name.
     */
    public function shortName(string $class): string
    {
        $separator = strrpos($class, '\\');

        return $separator === false ? $class : substr($class, $separator + 1);
    }
}
