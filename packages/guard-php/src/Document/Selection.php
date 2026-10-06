<?php

declare(strict_types=1);

namespace Guard\Document;

use JsonException;

/**
 * A selected value; missing fields remain distinct from explicit null.
 *
 * @property-read bool $exists
 * @property-read mixed $value
 */
final class Selection
{
    /** @readonly */
    private string $valueJson;

    /**
     * Creates the immutable value.
     * @param bool $exists
     * @param mixed $value
     * @throws JsonException
     */
    public function __construct(
        /** @readonly */
        private bool $exists,
        mixed $value,
    ) {
        $this->valueJson = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** Returns a declared immutable property.
     * @return mixed
     * @throws JsonException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'exists' => $this->exists,
            'value' => json_decode($this->valueJson, false, 512, JSON_THROW_ON_ERROR),
            default => null,
        };
    }
}
