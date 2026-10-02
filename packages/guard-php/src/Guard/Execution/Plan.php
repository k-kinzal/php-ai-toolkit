<?php

declare(strict_types=1);

namespace Toolkit\Guard\Execution;

/**
 * A repair plan evaluated before any file writes.
 *
 * @property-read list<\Toolkit\Guard\Reporting\Finding> $findings
 * @property-read list<FileChange> $changes
 */
final class Plan
{
    /**
     * Creates the immutable value.
     * @param list<\Toolkit\Guard\Reporting\Finding> $findings
     * @param list<FileChange> $changes
     */
    public function __construct(
        /** @readonly */
        private array $findings,
        /** @readonly */
        private array $changes,
    ) {
    }

    /** Returns a declared immutable property.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'findings' => $this->findings,
            'changes' => $this->changes,
            default => null,
        };
    }
}
