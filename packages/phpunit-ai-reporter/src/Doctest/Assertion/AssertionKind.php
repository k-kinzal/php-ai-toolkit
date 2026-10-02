<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Assertion;

/**
 * Types of assertions that can be made in doctest examples.
 *
 * Ported from the AssertionType enum of k-kinzal/doctest-php. This package
 * supports PHP 8.0, which has no enums, so the cases are class constants
 * carrying the same values the enum did. The name avoids the "Type" suffix the
 * toolkit forbids on class-likes.
 */
final class AssertionKind
{
    /**
     * Asserts that an expression returns a specific value.
     */
    public const RETURN_VALUE = 'return';

    /**
     * Asserts that code produces specific output.
     */
    public const OUTPUT = 'output';

    /**
     * Asserts that code throws a specific exception.
     */
    public const EXCEPTION = 'exception';
}
