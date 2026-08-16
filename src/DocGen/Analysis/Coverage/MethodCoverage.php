<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Coverage;

/**
 * Coverage figures of one method as reported by the PHPUnit XML report.
 *
 * @property-read int $executable
 * @property-read int $executed
 * @property-read float $percent
 */
final class MethodCoverage
{
    /**
     * Creates the coverage figures of one method.
     */
    public function __construct(
        /** @readonly */
        private int $executable,
        /** @readonly */
        private int $executed,
        /** @readonly */
        private float $percent,
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
            'executable' => $this->executable,
            'executed' => $this->executed,
            'percent' => $this->percent,
            default => null,
        };
    }
}
