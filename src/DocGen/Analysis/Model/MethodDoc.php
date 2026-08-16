<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

/**
 * One method declaration of a documented class-like symbol.
 *
 * @property-read string $name
 * @property-read string $visibility
 * @property-read bool $isStatic
 * @property-read bool $isAbstract
 * @property-read bool $isFinal
 * @property-read list<ParameterDoc> $parameters
 * @property-read TypeSignature $returnType
 * @property-read ?DocBlock $docBlock
 * @property-read int $startLine
 * @property-read int $endLine
 */
final class MethodDoc
{
    /**
     * @param list<ParameterDoc> $parameters
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $visibility,
        /** @readonly */
        private bool $isStatic,
        /** @readonly */
        private bool $isAbstract,
        /** @readonly */
        private bool $isFinal,
        /** @readonly */
        private array $parameters,
        /** @readonly */
        private TypeSignature $returnType,
        /** @readonly */
        private ?DocBlock $docBlock,
        /** @readonly */
        private int $startLine,
        /** @readonly */
        private int $endLine,
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
            'isAbstract' => $this->isAbstract,
            'isFinal' => $this->isFinal,
            'parameters' => $this->parameters,
            'returnType' => $this->returnType,
            'docBlock' => $this->docBlock,
            'startLine' => $this->startLine,
            'endLine' => $this->endLine,
            default => null,
        };
    }
}
