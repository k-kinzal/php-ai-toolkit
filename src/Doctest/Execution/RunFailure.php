<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

/**
 * One assertion of an example that did not hold.
 *
 * @property-read string $code
 * @property-read int $line
 * @property-read string $message
 * @property-read ?string $expected
 * @property-read ?string $actual
 */
final class RunFailure
{
    /**
     * @param string $code the statement source that failed
     * @param int $line the one-based line of the example the statement starts on
     * @param string $message what went wrong, phrased for the report
     * @param string|null $expected the rendered expected value, when there is one
     * @param string|null $actual the rendered actual value, when there is one
     */
    public function __construct(
        /** @readonly */
        private string $code,
        /** @readonly */
        private int $line,
        /** @readonly */
        private string $message,
        /** @readonly */
        private ?string $expected = null,
        /** @readonly */
        private ?string $actual = null,
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
            'line' => $this->line,
            'message' => $this->message,
            'expected' => $this->expected,
            'actual' => $this->actual,
            default => null,
        };
    }
}
