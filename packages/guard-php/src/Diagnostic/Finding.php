<?php

declare(strict_types=1);

namespace Guard\Diagnostic;

/**
 * An actionable guard diagnostic with a stable rule identifier.
 *
 * @property-read string $path
 * @property-read string $rule
 * @property-read string $level
 * @property-read bool $fixable
 * @property-read string $message
 */
final class Finding
{
    /**
     * Creates the immutable value.
     * @param string $path
     * @param string $rule
     * @param string $level
     * @param string $message
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $rule,
        /** @readonly */
        private string $level,
        /** @readonly */
        private string $message,
        /** @readonly */
        private bool $fixable = false,
    ) {
    }

    /** Returns a declared immutable property.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'rule' => $this->rule,
            'level' => $this->level,
            'message' => $this->message,
            'fixable' => $this->fixable,
            default => null,
        };
    }
}
