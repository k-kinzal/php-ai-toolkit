<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis;

use function count;

/**
 * Aggregated ScopeGuard analysis result for a configured project.
 *
 * @property-read int $fileCount
 * @property-read int $scopedDeclarationCount
 * @property-read int $referenceCount
 * @property-read list<Violation> $violations
 */
final class AnalysisResult
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(
        /** @readonly */
        private int $fileCount,
        /** @readonly */
        private int $scopedDeclarationCount,
        /** @readonly */
        private int $referenceCount,
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
            'fileCount' => $this->fileCount,
            'scopedDeclarationCount' => $this->scopedDeclarationCount,
            'referenceCount' => $this->referenceCount,
            'violations' => $this->violations,
            default => null,
        };
    }

    /**
     * Returns whether any declared scope was violated.
     */
    public function hasViolations(): bool
    {
        return $this->violations !== [];
    }

    /**
     * Returns the number of visibility violations.
     */
    public function violationCount(): int
    {
        return count($this->violations);
    }
}
