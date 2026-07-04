<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

/**
 * Numeric thresholds used by LocGuard.
 */
final class LimitConfig
{
    /**
     * Creates line-count and cyclomatic complexity thresholds.
     */
    public function __construct(
        public readonly int $maxFileLines,
        public readonly int $maxFileNcloc,
        public readonly int $maxClassLines,
        public readonly int $maxTraitLines,
        public readonly int $maxInterfaceLines,
        public readonly int $maxEnumLines,
        public readonly int $maxFunctionLines,
        public readonly int $maxMethodLines,
        public readonly int $maxCyclomaticComplexity,
    ) {
    }
}
