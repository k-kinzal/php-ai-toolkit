<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ExceptionHandling;

use PhpParser\Node\Name;

/**
 * One throw statement whose exception may escape the enclosing method.
 *
 * @property-read list<Name> $thrownNames
 * @property-read list<Name> $guardNames
 * @property-read int $line
 */
final class ThrowSite
{
    /**
     * Creates one throw site with its candidate exception names and guards.
     *
     * @param list<Name> $thrownNames class names the throw may raise
     * @param list<Name> $guardNames catch type names of the enclosing try blocks
     */
    public function __construct(
        /** @readonly */
        private array $thrownNames,
        /** @readonly */
        private array $guardNames,
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
            'thrownNames' => $this->thrownNames,
            'guardNames' => $this->guardNames,
            'line' => $this->line,
            default => null,
        };
    }
}
