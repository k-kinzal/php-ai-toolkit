<?php

declare(strict_types=1);

namespace Guard\Policy\Definition;

use function str_repeat;

/**
 * A heading declared for a document in doc-guard.yaml.
 *
 * @property-read int $level
 * @property-read string $text
 */
final class DeclaredHeading
{
    /**
     * Creates a declared heading from its level and normalized text.
     */
    public function __construct(
        /** @readonly */
        private int $level,
        /** @readonly */
        private string $text,
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
