<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown\Parsing;

use function str_repeat;

/**
 * A heading found in a Markdown document.
 *
 * @property-read int $level
 * @property-read string $text
 * @property-read int $line
 */
final class Heading
{
    /**
     * Creates a heading from its level, normalized text, and 1-based source line.
     */
    public function __construct(
        /** @readonly */
        private int $level,
        /** @readonly */
        private string $text,
        /** @readonly */
        private int $line,
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
            'line' => $this->line,
            default => null,
        };
    }

    /**
     * Returns the heading in ATX notation, such as "## Usage".
     */
    public function notation(): string
    {
        $marker = str_repeat('#', $this->level);

        return $this->text === '' ? $marker : $marker . ' ' . $this->text;
    }
}
