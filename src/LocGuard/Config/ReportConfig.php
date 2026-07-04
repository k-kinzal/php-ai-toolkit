<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

/**
 * Reporter selection and output ordering configuration.
 */
final class ReportConfig
{
    /**
     * @param list<string> $orderBy
     */
    public function __construct(
        public readonly string $reporter,
        public readonly array $orderBy,
    ) {
    }
}
