<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown;

/**
 * Parsed declared documents and discovery results grouped by scan pattern.
 *
 * @property-read array<string, ?list<Parsing\Heading>> $headings
 * @property-read list<string> $excluded
 * @property-read list<array{pattern: string, paths: list<string>}> $discovered
 */
final class MarkdownDocuments implements \Guard\Collect\Subject
{
    /**
     * @param array<string, ?list<Parsing\Heading>> $headings
     * @param list<string> $excluded
     * @param list<array{pattern: string, paths: list<string>}> $discovered
     */
    public function __construct(
        /** @readonly */
        private array $headings,
        /** @readonly */
        private array $excluded,
        /** @readonly */
        private array $discovered,
    ) {
    }

    /**
     * Returns a collected value.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'headings' => $this->headings,
            'excluded' => $this->excluded,
            'discovered' => $this->discovered,
            default => null,
        };
    }
}
