<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function get_object_vars;

/**
 * One reference to a documented symbol found in source code.
 *
 * The kind is one of "extends", "implements", "use-trait", "type", "new",
 * "static-call", "method-call", "class-const", "instanceof", or "attribute".
 *
 * @property-read string $targetFqcn
 * @property-read ?string $member
 * @property-read string $kind
 * @property-read ?string $fromFqcn
 * @property-read ?string $fromMember
 * @property-read string $file
 * @property-read int $line
 * @property-read bool $fromDev
 */
final class Usage
{
    /**
     * Creates one recorded reference.
     */
    public function __construct(
        /** @readonly */
        private string $targetFqcn,
        /** @readonly */
        private ?string $member,
        /** @readonly */
        private string $kind,
        /** @readonly */
        private ?string $fromFqcn,
        /** @readonly */
        private ?string $fromMember,
        /** @readonly */
        private string $file,
        /** @readonly */
        private int $line,
        /** @readonly */
        private bool $fromDev,
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
