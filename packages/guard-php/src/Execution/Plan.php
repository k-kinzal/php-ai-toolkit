<?php

declare(strict_types=1);

namespace Guard\Execution;

/**
 * A repair plan evaluated before any file writes.
 *
 * @property-read list<\Guard\Reporting\Finding> $findings
 * @property-read list<FileChange> $changes
 * @property-read list<\Guard\Reporting\Finding> $blockingFindings
 */
final class Plan
{
    /**
     * Creates the immutable value.
     * @param list<\Guard\Reporting\Finding> $findings
     * @param list<FileChange> $changes
     * @param list<\Guard\Reporting\Finding> $blockingFindings
     */
    public function __construct(
        /** @readonly */
        private array $findings,
        /** @readonly */
        private array $changes,
        /** @readonly */
        private array $blockingFindings = [],
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
            'blockingFindings' => $this->blockingFindings,
            default => null,
        };
    }
}
