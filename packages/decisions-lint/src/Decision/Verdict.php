<?php

declare(strict_types=1);

namespace DecisionsLint\Decision;

/**
 * The answers a decision model returned for one inquiry.
 *
 * @property-read string $model the exact model version that answered
 * @property-read array<string, Answer> $answers one answer per question name
 */
final class Verdict
{
    /**
     * Creates the immutable value.
     *
     * @param array<string, Answer> $answers
     */
    public function __construct(
        /** @readonly */
        private string $model,
        /** @readonly */
        private array $answers,
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
            'model' => $this->model,
            'answers' => $this->answers,
            default => null,
        };
    }

    /**
     * Returns the answer to a question, or a refusal when the model returned none.
     */
    public function answer(string $name): Answer
    {
        return $this->answers[$name] ?? Answer::refusal();
    }
}
