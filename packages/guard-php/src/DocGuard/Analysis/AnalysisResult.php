<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Analysis;

use function count;

/**
 * Aggregated DocGuard analysis result for a configuration.
 *
 * @property-read int $documents
 * @property-read int $headings
 * @property-read list<Violation> $violations
 */
final class AnalysisResult
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(
        /** @readonly */
        private int $documents,
        /** @readonly */
        private int $headings,
        /** @readonly */
        private array $violations,
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
            'documents' => $this->documents,
            'headings' => $this->headings,
            'violations' => $this->violations,
            default => null,
        };
    }

    /**
     * Returns whether any document deviates from its declared structure.
     */
    public function hasViolations(): bool
    {
        return $this->violations !== [];
    }

    /**
     * Returns the number of structure violations.
     */
    public function violationCount(): int
    {
        return count($this->violations);
    }
}
