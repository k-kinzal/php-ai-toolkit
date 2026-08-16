<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * One typed PHPDoc tag value, such as a param, return, var, or throws tag.
 *
 * @property-read ?TypeNode $type
 * @property-read string $description
 */
final class DocTag
{
    /**
     * Creates one typed tag value.
     */
    public function __construct(
        /** @readonly */
        private ?TypeNode $type,
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
            'type' => $this->type,
            'description' => $this->description,
            default => null,
        };
    }
}
