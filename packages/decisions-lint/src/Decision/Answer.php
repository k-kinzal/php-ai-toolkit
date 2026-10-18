<?php

declare(strict_types=1);

namespace DecisionsLint\Decision;

/**
 * The typed answer a decision model gave to one question.
 *
 * A predicate carries a probability that the statement holds. A choice carries
 * the chosen option, the probability of every option and a confidence. A score
 * carries the probability-weighted level, the probability of every level and a
 * confidence. A refusal carries nothing but the fact that the model declined.
 *
 * @property-read string $type predicate, choice, score or refusal
 * @property-read float $probability the probability that a predicate holds, from 0 to 1
 * @property-read string $choice the chosen option of a choice, or an empty string
 * @property-read float $score the weighted level of a score, from 0 to the number of levels minus one
 * @property-read float $confidence how peaked the distribution is, from 0 to 1
 * @property-read array<array-key, float> $probabilities the probability of every option or level, keyed by option name or level number
 */
final class Answer
{
    /**
     * The type of an answer the model declined to give.
     */
    public const REFUSAL = 'refusal';

    /**
     * Creates the immutable value.
     *
     * @param array<array-key, float> $probabilities
     */
    public function __construct(
        /** @readonly */
        private string $type,
        /** @readonly */
        private float $probability = 0.0,
        /** @readonly */
        private string $choice = '',
        /** @readonly */
        private float $score = 0.0,
        /** @readonly */
        private float $confidence = 0.0,
        /** @readonly */
        private array $probabilities = [],
    ) {
    }

    /**
     * Creates the answer to a predicate from the probability that it holds.
     */
    public static function predicate(float $probability): self
    {
        return new self(Question::PREDICATE, $probability, '', 0.0, max($probability, 1.0 - $probability), ['true' => $probability, 'false' => 1.0 - $probability]);
    }

    /**
     * Creates the answer to a choice from the chosen option and the distribution.
     *
     * @param array<array-key, float> $probabilities
     */
    public static function choice(string $choice, float $confidence, array $probabilities): self
    {
        return new self(Question::CHOICE, 0.0, $choice, 0.0, $confidence, $probabilities);
    }

    /**
     * Creates the answer to a score from the weighted level and the distribution.
     *
     * @param array<array-key, float> $probabilities
     */
    public static function score(float $score, float $confidence, array $probabilities): self
    {
        return new self(Question::SCORE, 0.0, '', $score, $confidence, $probabilities);
    }

    /**
     * Creates the answer of a model that declined to judge the subject.
     */
    public static function refusal(): self
    {
        return new self(self::REFUSAL);
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
            'probability' => $this->probability,
            'choice' => $this->choice,
            'score' => $this->score,
            'confidence' => $this->confidence,
            'probabilities' => $this->probabilities,
            default => null,
        };
    }
}
