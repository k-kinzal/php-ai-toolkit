<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

/**
 * A requirement that every class matching one template is accompanied by a class matching another.
 */
final class ClassPair
{
    /**
     * @param ClassNamePattern $from template of the class that requires a counterpart
     * @param ClassNamePattern $to template of the counterpart
     * @param ClassNameGlob $exclude classes this pair never checks
     */
    public function __construct(
        /** @readonly */
        private ClassNamePattern $from,
        /** @readonly */
        private ClassNamePattern $to,
        /** @readonly */
        private ClassNameGlob $exclude,
    ) {
    }

    /**
     * Returns the template of the class that requires a counterpart.
     */
    public function fromPattern(): string
    {
        return $this->from->pattern();
    }

    /**
     * Returns the template of the counterpart.
     */
    public function toPattern(): string
    {
        return $this->to->pattern();
    }

    /**
     * Reports whether the class is excluded from this pair.
     */
    public function excludes(string $class): bool
    {
        return $this->exclude->matches($class);
    }

    /**
     * Returns the counterpart a class matching the "from" template needs, or null when the class does not match.
     */
    public function counterpartOf(string $class): ?string
    {
        $name = $this->from->capture($class);
        if ($name === null) {
            return null;
        }

        $counterpart = $this->to->expand($name);

        return $counterpart === $class ? null : $counterpart;
    }

    /**
     * Returns the origin a class matching the "to" template needs, or null when the class does not match.
     */
    public function originOf(string $class): ?string
    {
        $name = $this->to->capture($class);
        if ($name === null) {
            return null;
        }

        $origin = $this->from->expand($name);

        return $origin === $class ? null : $origin;
    }
}
