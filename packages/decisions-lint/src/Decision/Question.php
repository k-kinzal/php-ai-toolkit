<?php

declare(strict_types=1);

namespace DecisionsLint\Decision;

/**
 * One typed question a decision model answers about a subject.
 *
 * A predicate asks for the probability that a statement holds, a choice asks
 * for one of several named options, and a score places the subject on an
 * ordered scale of levels.
 *
 * @property-read string $name the identifier the answer is returned under
 * @property-read string $type predicate, choice or score
 * @property-read string $instructions the full question text
 * @property-read array<string, string> $choices option name to description, for a choice
 * @property-read list<string> $levels level descriptions from lowest to highest, for a score
 */
final class Question
{
    /**
     * A yes-or-no judgement answered with a probability.
     */
    public const PREDICATE = 'predicate';

    /**
     * A pick from named options.
     */
    public const CHOICE = 'choice';

    /**
     * A position on an ordered scale.
     */
    public const SCORE = 'score';

    /**
     * Every question type.
     *
     * @var list<string>
     */
    public const TYPES = [self::PREDICATE, self::CHOICE, self::SCORE];

    /**
     * Creates the immutable value.
     *
     * @param array<string, string> $choices
     * @param list<string> $levels
     */
    public function __construct(
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $type,
        /** @readonly */
        private string $instructions,
        /** @readonly */
        private array $choices = [],
        /** @readonly */
        private array $levels = [],
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
            'name' => $this->name,
            'type' => $this->type,
            'instructions' => $this->instructions,
            'choices' => $this->choices,
            'levels' => $this->levels,
            default => null,
        };
    }
}
