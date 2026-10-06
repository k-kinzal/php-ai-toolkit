<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown;

/**
 * An open fenced code block, identified by its fence character and length.
 *
 * @property-read string $marker
 * @property-read int $length
 */
final class Fence
{
    /**
     * Creates an open fence from its character ("`" or "~") and its length.
     */
    public function __construct(
        /** @readonly */
        private string $marker,
        /** @readonly */
        private int $length,
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
            'marker' => $this->marker,
            'length' => $this->length,
            default => null,
        };
    }
}
