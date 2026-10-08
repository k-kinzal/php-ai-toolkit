<?php

declare(strict_types=1);

namespace Guard\Policy\Diagnostic;

/**
 * A single documentation document structure violation.
 *
 * The line is null for violations that have no location in a document, such
 * as a missing heading or a missing document. The expected and actual values
 * are headings in ATX notation, or null when the rule has none.
 *
 * @property-read string $path
 * @property-read ?int $line
 * @property-read string $rule
 * @property-read ?string $expected
 * @property-read ?string $actual
 * @property-read string $message
 */
final class HeadingViolation
{
    /**
     * Creates one structure violation.
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private ?int $line,
        /** @readonly */
        private string $rule,
        /** @readonly */
        private ?string $expected,
        /** @readonly */
        private ?string $actual,
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
        return match ($name) {
            'path' => $this->path,
            'line' => $this->line,
            'rule' => $this->rule,
            'expected' => $this->expected,
            'actual' => $this->actual,
            'message' => $this->message,
            default => null,
        };
    }
}
