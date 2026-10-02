<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Assertion;

use function get_class;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function var_export;

/**
 * Represents the result of evaluating an assertion.
 *
 * Contains information about whether the assertion passed, along with
 * details for generating meaningful error messages on failure.
 *
 * @template TExpected = mixed
 * @template TActual = mixed
 * @property-read bool $passed
 * @property-read string $message
 * @property-read Statement $statement
 * @property-read TExpected|null $expected
 * @property-read TActual|null $actual
 */
final class AssertionResult
{
    /**
     * @param bool $passed whether the assertion passed
     * @param string $message error message if failed
     * @param Statement $statement the statement that was evaluated
     * @param TExpected|null $expected the expected value, if applicable
     * @param TActual|null $actual the actual value, if applicable
     */
    public function __construct(
        /** @readonly */
        private bool $passed,
        /** @readonly */
        private string $message,
        /** @readonly */
        private Statement $statement,
        /** @readonly */
        private $expected = null,
        /** @readonly */
        private $actual = null,
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
            'passed' => $this->passed,
            'message' => $this->message,
            'statement' => $this->statement,
            'expected' => $this->expected,
            'actual' => $this->actual,
            default => null,
        };
    }

    /**
     * Returns a detailed message describing the result.
     *
     * For passing assertions, returns 'OK'. For failing assertions, includes
     * the code, expected value, actual value, and error message.
     */
    public function getDetailedMessage(): string
    {
        if ($this->passed) {
            return 'OK';
        }

        $parts = ['Assertion failed', 'Code: ' . $this->statement->code];
        if ($this->expected !== null) {
            $parts[] = 'Expected: ' . $this->formatValue($this->expected);
        }

        if ($this->actual !== null) {
            $parts[] = 'Actual: ' . $this->formatValue($this->actual);
        }

        if ($this->message !== '') {
            $parts[] = 'Message: ' . $this->message;
        }

        return implode("\n  ", $parts);
    }

    /**
     * Renders one value the way a failure report should show it.
     *
     * @param mixed $value the value to render
     */
    public function formatValue($value): string
    {
        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_array($value) ? var_export($value, true) : var_export($value, true);
    }
}
