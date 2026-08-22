<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Assertion;

/**
 * Represents a single statement within an example, with optional assertion.
 *
 * A Statement contains a piece of code and optionally an assertion about
 * what that code should produce. Statements without assertions serve as
 * "smoke tests" - they just verify the code runs without errors.
 *
 * @property-read string $code
 * @property-read ?Assertion $assertion
 * @property-read int $line
 */
final class Statement
{
    /**
     * @param string $code the code to execute
     * @param Assertion|null $assertion the assertion to verify, or null for a smoke test
     * @param int $line line number within the example
     */
    public function __construct(
        /** @readonly */
        private string $code,
        /** @readonly */
        private ?Assertion $assertion,
        /** @readonly */
        private int $line,
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
            'code' => $this->code,
            'assertion' => $this->assertion,
            'line' => $this->line,
            default => null,
        };
    }

    /**
     * Returns whether this statement has an assertion.
     *
     * Statements without assertions are "smoke tests" - they just verify
     * that the code executes without throwing exceptions.
     */
    public function hasAssertion(): bool
    {
        return $this->assertion !== null;
    }
}
