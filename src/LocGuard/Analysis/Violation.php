<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * A single LocGuard threshold violation.
 *
 * @property-read string $path
 * @property-read int $line
 * @property-read string $rule
 * @property-read int $actual
 * @property-read int $limit
 * @property-read string $message
 */
final class Violation
{
    /**
     * Creates one threshold violation with location, measured value, and message.
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private int $line,
        /** @readonly */
        private string $rule,
        /** @readonly */
        private int $actual,
        /** @readonly */
        private int $limit,
        /** @readonly */
        private string $message,
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
