<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Parse;

/**
 * The declarations and references extracted from the selected PHP files.
 *
 * @property-read list<Symbol\ClassLikeDoc> $classLikes
 * @property-read list<Symbol\FunctionDoc> $functions
 * @property-read list<Reference\Usage> $usages
 * @property-read list<string> $warnings
 */
final class ParsedProject
{
    /**
     * Creates the declarations and references extracted from the selected PHP files.
     * @param list<Symbol\ClassLikeDoc> $classLikes
     * @param list<Symbol\FunctionDoc> $functions
     * @param list<Reference\Usage> $usages
     * @param list<string> $warnings
     */
    public function __construct(
        /** @readonly */
        private array $classLikes,
        /** @readonly */
        private array $functions,
        /** @readonly */
        private array $usages,
        /** @readonly */
        private array $warnings,
    ) {
    }

    /**
     * Provides read-only access to the stage's values.
     *
     * @return mixed the requested value
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'classLikes' => $this->classLikes,
            'functions' => $this->functions,
            'usages' => $this->usages,
            'warnings' => $this->warnings,
            default => null,
        };
    }
}
