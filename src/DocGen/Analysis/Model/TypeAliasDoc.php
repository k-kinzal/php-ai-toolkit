<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * One local type alias declared with a phpstan-type or psalm-type tag.
 *
 * Imported aliases keep the fully qualified name of the declaring class in
 * importedFrom and carry no type of their own.
 *
 * @property-read string $name
 * @property-read ?TypeNode $type
 * @property-read ?string $importedFrom
 */
final class TypeAliasDoc
{
    /**
     * Creates one type alias model.
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private ?TypeNode $type,
        /** @readonly */
        private ?string $importedFrom,
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
            'importedFrom' => $this->importedFrom,
            default => null,
        };
    }
}
