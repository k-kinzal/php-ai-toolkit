<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Config;

/**
 * The declared heading structure of one Markdown document.
 *
 * Headings deeper than the maximum level are document content, not structure.
 *
 * @property-read string $path
 * @property-read list<DeclaredHeading> $headings
 * @property-read int $maxLevel
 */
final class DocumentConfig
{
    /**
     * @param list<DeclaredHeading> $headings
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private array $headings,
        /** @readonly */
        private int $maxLevel,
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
            'path' => $this->path,
            'headings' => $this->headings,
            'maxLevel' => $this->maxLevel,
            default => null,
        };
    }
}
