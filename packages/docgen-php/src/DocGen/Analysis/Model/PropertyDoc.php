<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Model;

/**
 * One property declaration, including constructor-promoted properties.
 *
 * @property-read string $name
 * @property-read string $visibility
 * @property-read bool $isStatic
 * @property-read bool $isPromoted
 * @property-read TypeSignature $type
 * @property-read ?string $defaultText
 * @property-read ?DocBlock $docBlock
 * @property-read int $line
 */
final class PropertyDoc
{
    /**
     * Creates one property declaration model.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $visibility,
        /** @readonly */
        private bool $isStatic,
        /** @readonly */
        private bool $isPromoted,
        /** @readonly */
        private TypeSignature $type,
        /** @readonly */
        private ?string $defaultText,
        /** @readonly */
        private ?DocBlock $docBlock,
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
            'name' => $this->name,
            'visibility' => $this->visibility,
            'isStatic' => $this->isStatic,
            'isPromoted' => $this->isPromoted,
            'type' => $this->type,
            'defaultText' => $this->defaultText,
            'docBlock' => $this->docBlock,
            'line' => $this->line,
            default => null,
        };
    }
}
