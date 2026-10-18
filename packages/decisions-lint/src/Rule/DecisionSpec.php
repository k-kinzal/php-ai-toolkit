<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

/**
 * The question a rule asks about each target and how sure the model has to be.
 *
 * @property-read string $type predicate, choice or score
 * @property-read string $instructions the question template
 * @property-read array<string, string> $choices option name to description, for a choice
 * @property-read list<string> $levels level descriptions from lowest to highest, for a score
 * @property-read float $threshold the minimum probability or confidence a violation needs, from 0 to 1
 */
final class DecisionSpec
{
    /**
     * Creates the immutable value.
     *
     * @param array<string, string> $choices
     * @param list<string> $levels
     */
    public function __construct(
        /** @readonly */
        private string $type,
        /** @readonly */
        private string $instructions,
        /** @readonly */
        private array $choices = [],
        /** @readonly */
        private array $levels = [],
        /** @readonly */
        private float $threshold = 0.5,
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'type' => $this->type,
            'instructions' => $this->instructions,
            'choices' => $this->choices,
            'levels' => $this->levels,
            'threshold' => $this->threshold,
            default => null,
        };
    }
}
