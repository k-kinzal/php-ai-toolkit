<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown\Badge;

/**
 * A linked image written as [![label](image)](link).
 *
 * @property-read string $label
 * @property-read string $image
 * @property-read string $link
 * @property-read int $line
 */
final class Badge
{
    /**
     * Creates a badge from its alternative text, image URL, link target, and 1-based source line.
     */
    public function __construct(
        /** @readonly */
        private string $label,
        /** @readonly */
        private string $image,
        /** @readonly */
        private string $link,
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
            'label' => $this->label,
            'image' => $this->image,
            'link' => $this->link,
            'line' => $this->line,
            default => null,
        };
    }
}
