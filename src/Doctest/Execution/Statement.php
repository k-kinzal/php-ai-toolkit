<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

/**
 * One executable line group of an example, with the assertion it carries.
 *
 * A statement without a marker is a smoke test: it passes as long as running
 * it raises nothing.
 *
 * @property-read string $code
 * @property-read ?string $marker
 * @property-read ?string $expected
 * @property-read ?string $exceptionMessage
 * @property-read int $line
 */
final class Statement
{
    /**
     * @param string $code the PHP source to evaluate
     * @param string|null $marker the assertion marker: return, output, throws, or null
     * @param string|null $expected the raw expected expression, output, or exception class
     * @param string|null $exceptionMessage the expected exception message fragment
     * @param int $line the one-based line of the example the statement starts on
     */
    public function __construct(
        /** @readonly */
        private string $code,
        /** @readonly */
        private ?string $marker,
        /** @readonly */
        private ?string $expected,
        /** @readonly */
        private ?string $exceptionMessage,
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
            'marker' => $this->marker,
            'expected' => $this->expected,
            'exceptionMessage' => $this->exceptionMessage,
            'line' => $this->line,
            default => null,
        };
    }
}
