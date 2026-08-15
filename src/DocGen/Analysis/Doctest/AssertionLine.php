<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Doctest;

use function get_object_vars;

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
        return get_object_vars($this)[$name] ?? null;
    }
}
