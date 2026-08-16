<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

/**
 * The complete type of one declaration site.
 *
 * The native part is the declared PHP type as source text; the annotated
 * part is the richer PHPDoc tag, which takes precedence when rendering.
 *
 * @property-read ?string $native
 * @property-read ?DocTag $annotated
 */
final class TypeSignature
{
    /**
     * Creates the declared type of one site.
     */
    public function __construct(
        /** @readonly */
        private ?string $native,
        /** @readonly */
        private ?DocTag $annotated,
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
            'native' => $this->native,
            'annotated' => $this->annotated,
            default => null,
        };
    }
}
