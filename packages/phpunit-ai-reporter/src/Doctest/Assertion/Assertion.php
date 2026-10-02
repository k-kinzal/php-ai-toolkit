<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Assertion;

/**
 * Represents an assertion to verify in a doctest example.
 *
 * An assertion specifies what should be checked after executing code:
 * - RETURN_VALUE: Check that the expression returns a specific value
 * - OUTPUT: Check that the code produces specific output
 * - EXCEPTION: Check that the code throws a specific exception
 *
 * @property-read string $type
 * @property-read string $expectedRaw
 * @property-read ?string $exceptionMessage
 */
final class Assertion
{
    /**
     * @param string $type the assertion kind, one of the AssertionKind constants
     * @param string $expectedRaw the raw expected value or exception class name
     * @param string|null $exceptionMessage expected exception message, for the exception kind
     */
    public function __construct(
        /** @readonly */
        private string $type,
        /** @readonly */
        private string $expectedRaw,
        /** @readonly */
        private ?string $exceptionMessage = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'type' => $this->type,
            'expectedRaw' => $this->expectedRaw,
            'exceptionMessage' => $this->exceptionMessage,
            default => null,
        };
    }
}
