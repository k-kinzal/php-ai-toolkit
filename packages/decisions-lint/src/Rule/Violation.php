<?php

declare(strict_types=1);

namespace DecisionsLint\Rule;

/**
 * One answer a rule treats as a violation, and what to tell the author about it.
 *
 * A predicate violation names the boolean answer, a choice violation names the
 * option, and a score violation bounds the score with min_score and max_score.
 *
 * @property-read bool|string|null $answer the predicate or choice answer that violates, or null for a score
 * @property-read ?float $minScore the lowest score that violates, inclusive
 * @property-read ?float $maxScore the highest score that violates, inclusive
 * @property-read string $message the template saying what is wrong
 * @property-read string $fix the template saying what to change
 */
final class Violation
{
    /**
     * Creates the immutable value.
     *
     * @param bool|string|null $answer
     */
    public function __construct(
        /** @readonly */
        private $answer,
        /** @readonly */
        private ?float $minScore,
        /** @readonly */
        private ?float $maxScore,
        /** @readonly */
        private string $message,
        /** @readonly */
        private string $fix,
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
            'answer' => $this->answer,
            'minScore' => $this->minScore,
            'maxScore' => $this->maxScore,
            'message' => $this->message,
            'fix' => $this->fix,
            default => null,
        };
    }
}
