<?php

declare(strict_types=1);

namespace Guard\Execution;

use Guard\Policy\PolicyException;

/**
 * Combines policy repairs without allowing competing replacements of one file.
 */
final class ChangeSet
{
    /** @var array<string, FileChange> */
    private array $changes = [];

    /**
     * Coalesces identical proposals and rejects conflicts before any writes.
     * @throws PolicyException when policies propose incompatible file replacements
     */
    public function add(FileChange $change): void
    {
        $existing = $this->changes[$change->path] ?? null;
        if ($existing !== null && ($existing->original !== $change->original || $existing->replacement !== $change->replacement)) {
            throw new PolicyException('Policies propose conflicting repairs for ' . $change->path . '. Coordinate repairs in one policy for this file. No changes were written.');
        }
        $this->changes[$change->path] = $change;
    }

    /**
     * Returns one repair per file in first-proposal order.
     * @return list<FileChange>
     */
    public function changes(): array
    {
        return array_values($this->changes);
    }
}
