<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

use function get_object_vars;

/**
 * One class constant declaration.
 *
 * @property-read string $name
 * @property-read string $visibility
 * @property-read ?string $valueText
 * @property-read ?DocBlock $docBlock
 * @property-read int $line
 */
final class ConstantDoc
{
    /**
     * Creates one constant declaration model.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $visibility,
        /** @readonly */
        private ?string $valueText,
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
        return get_object_vars($this)[$name] ?? null;
    }
}
