<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Model;

use function get_object_vars;

/**
 * One documented top-level function.
 *
 * @property-read string $fqn
 * @property-read string $shortName
 * @property-read string $namespace
 * @property-read string $packageName
 * @property-read string $file
 * @property-read int $startLine
 * @property-read int $endLine
 * @property-read list<ParameterDoc> $parameters
 * @property-read TypeSignature $returnType
 * @property-read ?DocBlock $docBlock
 * @property-read array<string, string> $useMap
 * @property-read bool $isDev
 */
final class FunctionDoc
{
    /**
     * @param list<ParameterDoc> $parameters
     * @param array<string, string> $useMap
     */
    public function __construct(
        /** @readonly */
        private string $fqn,
        /** @readonly */
        private string $shortName,
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private string $packageName,
        /** @readonly */
        private string $file,
        /** @readonly */
        private int $startLine,
        /** @readonly */
        private int $endLine,
        /** @readonly */
        private array $parameters,
        /** @readonly */
        private TypeSignature $returnType,
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
        return get_object_vars($this)[$name] ?? null;
    }
}
