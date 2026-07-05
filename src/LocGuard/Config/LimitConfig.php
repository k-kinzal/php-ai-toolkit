<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

/**
 * Numeric thresholds used by LocGuard.
 *
 * @property-read int $maxFileLines
 * @property-read int $maxFileNcloc
 * @property-read int $maxClassLines
 * @property-read int $maxTraitLines
 * @property-read int $maxInterfaceLines
 * @property-read int $maxEnumLines
 * @property-read int $maxFunctionLines
 * @property-read int $maxMethodLines
 * @property-read int $maxCyclomaticComplexity
 */
final class LimitConfig
{
    /**
     * Creates line-count and cyclomatic complexity thresholds.
     */
    public function __construct(
        /** @readonly */
        private int $maxFileLines,
        /** @readonly */
        private int $maxFileNcloc,
        /** @readonly */
        private int $maxClassLines,
        /** @readonly */
        private int $maxTraitLines,
        /** @readonly */
        private int $maxInterfaceLines,
        /** @readonly */
        private int $maxEnumLines,
        /** @readonly */
        private int $maxFunctionLines,
        /** @readonly */
        private int $maxMethodLines,
        /** @readonly */
        private int $maxCyclomaticComplexity,
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
