<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

/**
 * Reporter selection and output ordering configuration.
 *
 * @property-read string $reporter
 * @property-read list<string> $orderBy
 */
final class ReportConfig
{
    /**
     * @param list<string> $orderBy
     */
    public function __construct(
        /** @readonly */
        private string $reporter,
        /** @readonly */
        private array $orderBy,
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
