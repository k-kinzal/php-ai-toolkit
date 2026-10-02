<?php

declare(strict_types=1);

namespace Toolkit\Mutation;

use function in_array;

/**
 * Mutation effects declared by one callable PHPDoc block.
 */
final class MutationContract
{
    /**
     * @param list<string> $mutableParameters parameter names without the dollar sign
     * @param list<string> $problems invalid pieces of mutation syntax
     */
    public function __construct(
        /** @readonly */
        private array $mutableParameters = [],
        /** @readonly */
        private bool $mutatesThis = false,
        /** @readonly */
        private bool $mutatesGlobal = false,
        /** @readonly */
        private array $problems = [],
    ) {
    }

    /**
     * Reports whether the named parameter carries +mut.
     */
    public function mutatesParameter(string $name): bool
    {
        return in_array($name, $this->mutableParameters, true);
    }

    /**
     * @return list<string>
     */
    public function mutableParameters(): array
    {
        return $this->mutableParameters;
    }

    /**
     * Reports whether the callable may change its instance receiver.
     */
    public function mutatesThis(): bool
    {
        return $this->mutatesThis;
    }

    /**
     * Reports whether the callable may change state outside its receiver.
     */
    public function mutatesGlobal(): bool
    {
        return $this->mutatesGlobal;
    }

    /**
     * @return list<string>
     */
    public function problems(): array
    {
        return $this->problems;
    }
}
