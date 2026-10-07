<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest\Project;

use InvalidArgumentException;

/**
 * Adds and divides numbers.
 *
 * @example Building a calculator
 *     $calculator = new \Tests\Fixture\Doctest\Project\Calculator();
 *     $calculator instanceof \Tests\Fixture\Doctest\Project\Calculator // => true
 */
final class Calculator
{
    /**
     * Adds two numbers.
     *
     * @example Adding two numbers
     *     (new \Tests\Fixture\Doctest\Project\Calculator())->add(1, 2) // => 3
     *
     * @example Adding across several lines
     *     $calculator = new \Tests\Fixture\Doctest\Project\Calculator();
     *     $calculator->add(
     *         10,
     *         5) // => 15
     */
    public function add(int $left, int $right): int
    {
        return $left + $right;
    }

    /**
     * Divides two numbers.
     *
     * @example Refusing to divide by zero
     *     (new \Tests\Fixture\Doctest\Project\Calculator())->divide(1, 0) // throws InvalidArgumentException: divide by zero
     */
    public function divide(int $left, int $right): int
    {
        if ($right === 0) {
            throw new InvalidArgumentException('Cannot divide by zero');
        }

        return intdiv($left, $right);
    }

    /**
     * Prints the sum.
     *
     * @example Printing a sum
     *     (new \Tests\Fixture\Doctest\Project\Calculator())->printSum(2, 3); // Output: 5
     */
    public function printSum(int $left, int $right): void
    {
        echo $this->add($left, $right);
    }

    /**
     * Documents a shape without running it.
     *
     * @example $calculator->add($left, $right)
     */
    public function shape(): string
    {
        return 'shape';
    }
}
