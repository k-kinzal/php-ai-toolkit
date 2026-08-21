<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest\Failing;

/**
 * Documents a value it does not produce.
 *
 * @example Claiming the wrong sum
 *     (new Broken())->add(1, 2) // => 4
 */
final class Broken
{
    /**
     * Adds two numbers.
     */
    public function add(int $left, int $right): int
    {
        return $left + $right;
    }
}
