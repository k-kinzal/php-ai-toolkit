<?php

declare(strict_types=1);

namespace Guard\Policy\Definition;

use function fnmatch;

/**
 * One position of the declared badge block that follows a document title.
 *
 * A badge matches when its image URL matches the glob pattern and, when a
 * label is declared, its alternative text equals the label.
 *
 * @property-read string $name
 * @property-read string $image
 * @property-read ?string $label
 * @property-read bool $optional
 * @property-read bool $repeat
 */
final class BadgeEntry
{
    /**
     * Creates a badge entry from its name, image pattern, optional label, and occurrence bounds.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $image,
        /** @readonly */
        private ?string $label = null,
        /** @readonly */
        private bool $optional = false,
        /** @readonly */
        private bool $repeat = false,
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
            'name' => $this->name,
            'image' => $this->image,
            'label' => $this->label,
            'optional' => $this->optional,
            'repeat' => $this->repeat,
            default => null,
        };
    }

    /**
     * Reports whether a badge with this label and image URL fills the entry.
     */
    public function accepts(string $label, string $image): bool
    {
        return fnmatch($this->image, $image) && ($this->label === null || $this->label === $label);
    }
}
