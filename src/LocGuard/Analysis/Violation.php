<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

/**
 * A single LocGuard threshold violation.
 */
final class Violation
{
    /**
     * Creates one threshold violation with location, measured value, and message.
     */
    public function __construct(
        public readonly string $path,
        public readonly int $line,
        public readonly string $rule,
        public readonly int $actual,
        public readonly int $limit,
        public readonly string $message,
    ) {
    }
}
