<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Analysis;

/**
 * A single TreeGuard structure violation.
 *
 * The actual and limit values are null for constraints that are not
 * count-based, such as naming and required-file checks.
 *
 * @property-read string $path
 * @property-read string $rule
 * @property-read string $pattern
 * @property-read ?int $actual
 * @property-read ?int $limit
 * @property-read string $message
 */
final class Violation
{
    /**
     * Creates one structure violation with its origin rule pattern and message.
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $rule,
        /** @readonly */
        private string $pattern,
        /** @readonly */
        private ?int $actual,
        /** @readonly */
        private ?int $limit,
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
            'rule' => $this->rule,
            'pattern' => $this->pattern,
            'actual' => $this->actual,
            'limit' => $this->limit,
            'message' => $this->message,
            default => null,
        };
    }
}
