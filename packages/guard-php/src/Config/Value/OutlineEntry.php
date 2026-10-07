<?php

declare(strict_types=1);

namespace Guard\Config\Value;

use function array_map;
use function array_merge;
use function str_repeat;

/**
 * One position of a declared document outline.
 *
 * The text "*" is a wildcard: it matches any heading of the same level whose
 * text is not named by another entry of the same outline. Alternatives let one
 * position accept any of several named headings, such as "## Vision" or
 * "## Product Vision".
 *
 * @property-read int $level
 * @property-read string $text
 * @property-read bool $optional
 * @property-read bool $repeat
 * @property-read list<DeclaredHeading> $alternatives
 */
final class OutlineEntry
{
    /**
     * Creates an outline entry from its heading, its occurrence bounds, and further accepted headings.
     *
     * @param list<DeclaredHeading> $alternatives
     */
    public function __construct(
        /** @readonly */
        private int $level,
        /** @readonly */
        private string $text,
        /** @readonly */
        private bool $optional = false,
        /** @readonly */
        private bool $repeat = false,
        /** @readonly */
        private array $alternatives = [],
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
            'level' => $this->level,
            'text' => $this->text,
            'optional' => $this->optional,
            'repeat' => $this->repeat,
            'alternatives' => $this->alternatives,
            default => null,
        };
    }

    /**
     * Reports whether the entry matches any heading text of its level.
     */
    public function wildcard(): bool
    {
        return $this->text === '*';
    }

    /**
     * Returns every heading the entry names, the primary one first.
     *
     * @return list<DeclaredHeading>
     */
    public function headings(): array
    {
        return array_merge([new DeclaredHeading($this->level, $this->text)], $this->alternatives);
    }

    /**
     * Reports whether a heading with this level and text fills a named entry.
     */
    public function names(int $level, string $text): bool
    {
        foreach ($this->headings() as $heading) {
            if ($heading->level === $level && $heading->text === $text) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the primary heading in ATX notation, such as "## Usage" or "## *".
     */
    public function notation(): string
    {
        return str_repeat('#', $this->level) . ' ' . $this->text;
    }

    /**
     * Returns every accepted heading in ATX notation.
     *
     * @return list<string>
     */
    public function notations(): array
    {
        return array_map(static fn (DeclaredHeading $heading): string => $heading->notation(), $this->headings());
    }
}
