<?php

declare(strict_types=1);

namespace DecisionsLint\Lint;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\Severity;

/**
 * The outcome of a run: the findings and the numbers that put them in proportion.
 *
 * @property-read list<Finding> $findings the findings sorted by file, line, column and rule
 * @property-read int $files how many files were read
 * @property-read int $targets how many declarations were judged
 * @property-read int $requests how many requests the provider answered
 * @property-read list<string> $models the model versions that answered
 * @property-read string $failOn the weakest severity that fails the run, or never
 */
final class Report
{
    /**
     * Creates the immutable value.
     *
     * @param list<Finding> $findings
     * @param list<string> $models
     */
    public function __construct(
        /** @readonly */
        private array $findings,
        /** @readonly */
        private int $files,
        /** @readonly */
        private int $targets,
        /** @readonly */
        private int $requests,
        /** @readonly */
        private array $models,
        /** @readonly */
        private string $failOn,
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
            'findings' => $this->findings,
            'files' => $this->files,
            'targets' => $this->targets,
            'requests' => $this->requests,
            'models' => $this->models,
            'failOn' => $this->failOn,
            default => null,
        };
    }

    /**
     * Returns how many findings each severity has.
     *
     * @return array{error: int, warning: int, info: int}
     */
    public function counts(): array
    {
        $counts = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($this->findings as $finding) {
            if (array_key_exists($finding->severity, $counts)) {
                ++$counts[$finding->severity];
            }
        }

        return $counts;
    }

    /**
     * Tells whether a finding is at least as severe as the configured threshold.
     */
    public function failed(): bool
    {
        $severity = new Severity();
        foreach ($this->findings as $finding) {
            if ($severity->atLeast($finding->severity, $this->failOn)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns how many distinct files have findings.
     */
    public function filesWithFindings(): int
    {
        $paths = [];
        foreach ($this->findings as $finding) {
            $paths[$finding->path] = true;
        }

        return count($paths);
    }
}
