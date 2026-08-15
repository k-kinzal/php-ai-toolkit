<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Coverage;

use function get_object_vars;

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
        return get_object_vars($this)[$name] ?? null;
    }
}
