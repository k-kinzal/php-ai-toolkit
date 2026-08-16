<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

use function get_object_vars;

/**
 * One documented class, interface, trait, or enum.
 *
 * The use map keeps the file's import aliases (lowercased alias to fully
 * qualified name) so PHPDoc type names can be resolved when rendering.
 *
 * @property-read string $fqcn
 * @property-read string $shortName
 * @property-read string $namespace
 * @property-read string $kind
 * @property-read string $packageName
 * @property-read string $file
 * @property-read int $startLine
 * @property-read int $endLine
 * @property-read bool $isAbstract
 * @property-read bool $isFinal
 * @property-read list<string> $extends
 * @property-read list<string> $implements
 * @property-read list<string> $traits
 * @property-read list<ConstantDoc> $constants
 * @property-read list<PropertyDoc> $properties
 * @property-read list<MethodDoc> $methods
 * @property-read list<EnumCaseDoc> $enumCases
 * @property-read ?string $backingType
 * @property-read ?DocBlock $docBlock
 * @property-read array<string, string> $useMap
 * @property-read bool $isDev
 */
final class ClassLikeDoc
{
    /**
     * The property table of this symbol, built on the first read.
     *
     * Every other value object of the toolkit answers __get() with a match
     * over its properties, but a symbol declares more of them than one
     * method may branch on under the configured complexity limit, so this
     * one builds its table once instead. Rebuilding it on every read is
     * what a symbol that hundreds of pages ask about cannot afford.
     *
     * @var ?array<mixed>
     */
    private ?array $propertyValues = null;

    /**
     * @param list<string> $extends
     * @param list<string> $implements
     * @param list<string> $traits
     * @param list<ConstantDoc> $constants
     * @param list<PropertyDoc> $properties
     * @param list<MethodDoc> $methods
     * @param list<EnumCaseDoc> $enumCases
     * @param array<string, string> $useMap
     */
    public function __construct(
        /** @readonly */
        private string $fqcn,
        /** @readonly */
        private string $shortName,
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $packageName,
        /** @readonly */
        private string $file,
        /** @readonly */
        private int $startLine,
        /** @readonly */
        private int $endLine,
        /** @readonly */
        private bool $isAbstract,
        /** @readonly */
        private bool $isFinal,
        /** @readonly */
        private array $extends,
        /** @readonly */
        private array $implements,
        /** @readonly */
        private array $traits,
        /** @readonly */
        private array $constants,
        /** @readonly */
        private array $properties,
        /** @readonly */
        private array $methods,
        /** @readonly */
        private array $enumCases,
        /** @readonly */
        private ?string $backingType,
        /** @readonly */
        private ?DocBlock $docBlock,
        /** @readonly */
        private array $useMap,
        /** @readonly */
        private bool $isDev,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        $this->propertyValues ??= get_object_vars($this);

        return $this->propertyValues[$name] ?? null;
    }
}
