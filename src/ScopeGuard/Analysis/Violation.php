<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis;

/**
 * A single ScopeGuard visibility violation.
 *
 * @property-read string $path
 * @property-read int $line
 * @property-read string $rule
 * @property-read string $symbol
 * @property-read string $message
 */
final class Violation
{
    /**
     * @param string $rule the violated check, out_of_scope or invalid_scope
     * @param string $symbol the declaration the violation is about
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private int $line,
        /** @readonly */
        private string $rule,
        /** @readonly */
        private string $symbol,
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
            'symbol' => $this->symbol,
            'message' => $this->message,
            default => null,
        };
    }
}
