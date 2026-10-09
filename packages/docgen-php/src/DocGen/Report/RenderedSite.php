<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Report;

/**
 * The pages written by the report stage and its output warnings.
 *
 * @property-read int $pages
 * @property-read list<string> $warnings
 */
final class RenderedSite
{
    /**
     * Creates the pages written by the report stage and its output warnings.
     * @param list<string> $warnings
     */
    public function __construct(
        /** @readonly */
        private int $pages,
        /** @readonly */
        private array $warnings = [],
    ) {
    }

    /**
     * Provides read-only access to the stage's values.
     *
     * @return mixed the requested value
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'pages' => $this->pages,
            'warnings' => $this->warnings,
            default => null,
        };
    }
}
