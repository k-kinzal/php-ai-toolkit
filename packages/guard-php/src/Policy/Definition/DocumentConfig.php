<?php

declare(strict_types=1);

namespace Guard\Policy\Definition;

/**
 * The declared structure of one Markdown document.
 *
 * Headings deeper than the maximum level are document content, not structure.
 * Each check is optional: null headings, no outlines, null badges and null
 * content leave that aspect of the document unchecked.
 *
 * @property-read string $path
 * @property-read ?list<DeclaredHeading> $headings
 * @property-read int $maxLevel
 * @property-read array<string, list<OutlineEntry>> $outlines
 * @property-read ?list<BadgeEntry> $badges
 * @property-read ?string $content
 */
final class DocumentConfig
{
    /**
     * @param ?list<DeclaredHeading> $headings
     * @param array<string, list<OutlineEntry>> $outlines
     * @param ?list<BadgeEntry> $badges
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private ?array $headings,
        /** @readonly */
        private int $maxLevel,
        /** @readonly */
        private array $outlines = [],
        /** @readonly */
        private ?array $badges = null,
        /** @readonly */
        private ?string $content = null,
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
            'outlines' => $this->outlines,
            'badges' => $this->badges,
            'content' => $this->content,
            default => null,
        };
    }
}
