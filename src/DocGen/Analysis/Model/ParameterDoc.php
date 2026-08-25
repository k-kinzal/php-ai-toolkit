<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Model;

/**
 * One function or method parameter.
 *
 * @property-read string $name
 * @property-read TypeSignature $type
 * @property-read bool $byRef
 * @property-read bool $variadic
 * @property-read ?string $defaultText
 * @property-read ?string $promotedVisibility
 * @property-read string $description
 */
final class ParameterDoc
{
    /**
     * Creates one parameter model.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private TypeSignature $type,
        /** @readonly */
        private bool $byRef,
        /** @readonly */
        private bool $variadic,
        /** @readonly */
        private ?string $defaultText,
        /** @readonly */
        private ?string $promotedVisibility,
        /** @readonly */
        private string $description,
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
            'type' => $this->type,
            'byRef' => $this->byRef,
            'variadic' => $this->variadic,
            'defaultText' => $this->defaultText,
            'promotedVisibility' => $this->promotedVisibility,
            'description' => $this->description,
            default => null,
        };
    }
}
