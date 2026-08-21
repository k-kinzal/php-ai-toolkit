<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

/**
 * One example line classified by its doctest assertion marker.
 *
 * The marker is "return", "output", or "throws", or null for plain
 * smoke-test lines.
 *
 * @property-read string $text
 * @property-read string $code
 * @property-read ?string $marker
 * @property-read ?string $expected
 * @property-read ?string $exceptionMessage
 *
 * @visibility public
 *
 * @example Reading the parts of a classified line
 *     $line = new AssertionLine('add(1, 2) // => 3', 'add(1, 2)', 'return', '3', null);
 *     $line->code // => 'add(1, 2)'
 *     $line->marker // => 'return'
 *     $line->expected // => '3'
 */
final class AssertionLine
{
    /**
     * Creates one classified example line.
     */
    public function __construct(
        /** @readonly */
        private string $text,
        /** @readonly */
        private string $code,
        /** @readonly */
        private ?string $marker,
        /** @readonly */
        private ?string $expected,
        /** @readonly */
        private ?string $exceptionMessage,
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
            'text' => $this->text,
            'code' => $this->code,
            'marker' => $this->marker,
            'expected' => $this->expected,
            'exceptionMessage' => $this->exceptionMessage,
            default => null,
        };
    }
}
