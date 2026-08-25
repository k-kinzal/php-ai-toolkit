<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Model;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * One generic template parameter declared with a template tag.
 *
 * @property-read string $name
 * @property-read ?TypeNode $bound
 * @property-read string $description
 */
final class TemplateDoc
{
    /**
     * Creates one template parameter model.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private ?TypeNode $bound,
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
            'bound' => $this->bound,
            'description' => $this->description,
            default => null,
        };
    }
}
