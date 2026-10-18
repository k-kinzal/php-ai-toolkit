<?php

declare(strict_types=1);

namespace DecisionsLint\Lint;

use DecisionsLint\Decision\Inquiry;

/**
 * Everything a run will ask the model, worked out before any request is sent.
 *
 * @property-read list<string> $files the files that were read
 * @property-read list<Inquiry> $inquiries one request per judged target and context
 * @property-read list<Assignment> $assignments one entry per rule and target
 */
final class Plan
{
    /**
     * Creates the immutable value.
     *
     * @param list<string> $files
     * @param list<Inquiry> $inquiries
     * @param list<Assignment> $assignments
     */
    public function __construct(
        /** @readonly */
        private array $files,
        /** @readonly */
        private array $inquiries,
        /** @readonly */
        private array $assignments,
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'files' => $this->files,
            'inquiries' => $this->inquiries,
            'assignments' => $this->assignments,
            default => null,
        };
    }

    /**
     * Returns how many distinct declarations are judged.
     */
    public function targets(): int
    {
        $seen = [];
        foreach ($this->assignments as $assignment) {
            $seen[$assignment->target->file . '|' . $assignment->target->qualifiedName . '|' . $assignment->target->line] = true;
        }

        return count($seen);
    }
}
