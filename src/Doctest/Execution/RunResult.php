<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\Example;

/**
 * What running one example produced.
 *
 * @property-read Example $example
 * @property-read list<RunFailure> $failures
 * @property-read bool $skipped
 */
final class RunResult
{
    /**
     * @param Example $example the example that was run
     * @param list<RunFailure> $failures the assertions that did not hold
     * @param bool $skipped whether the example was display-only and never run
     */
    public function __construct(
        /** @readonly */
        private Example $example,
        /** @readonly */
        private array $failures,
        /** @readonly */
        private bool $skipped = false,
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
            'example' => $this->example,
            'failures' => $this->failures,
            'skipped' => $this->skipped,
            default => null,
        };
    }

    /**
     * Reports whether every assertion of the example held.
     */
    public function passed(): bool
    {
        return $this->failures === [];
    }

    /**
     * Returns the failure report for the example, empty when it passed.
     */
    public function errorMessage(): string
    {
        $blocks = [];
        foreach ($this->failures as $failure) {
            $block = sprintf("line %d: %s\n  code: %s", $failure->line, $failure->message, $failure->code);
            if ($failure->expected !== null) {
                $block .= sprintf("\n  expected: %s", $failure->expected);
            }

            if ($failure->actual !== null) {
                $block .= sprintf("\n  actual: %s", $failure->actual);
            }

            $blocks[] = $block;
        }

        return implode("\n", $blocks);
    }
}
