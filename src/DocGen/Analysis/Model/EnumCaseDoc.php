<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

/**
 * One enum case declaration.
 *
 * @property-read string $name
 * @property-read ?string $valueText
 * @property-read ?DocBlock $docBlock
 * @property-read int $line
 */
final class EnumCaseDoc
{
    /**
     * Creates one enum case model.
     */
    public function __construct(
        /** @readonly */
        private string $name,
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
        return match ($name) {
            'name' => $this->name,
            'valueText' => $this->valueText,
            'docBlock' => $this->docBlock,
            'line' => $this->line,
            default => null,
        };
    }
}
