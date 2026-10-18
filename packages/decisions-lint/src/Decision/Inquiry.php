<?php

declare(strict_types=1);

namespace DecisionsLint\Decision;

/**
 * One subject and every question to answer about it, sent to the model as one request.
 *
 * @property-read array<string, string> $subject named sections of text the questions are about
 * @property-read list<Question> $questions the questions, each with a unique name
 */
final class Inquiry
{
    /**
     * Creates the immutable value.
     *
     * @param array<string, string> $subject
     * @param list<Question> $questions
     */
    public function __construct(
        /** @readonly */
        private array $subject,
        /** @readonly */
        private array $questions,
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
            'subject' => $this->subject,
            'questions' => $this->questions,
            default => null,
        };
    }

    /**
     * Returns the question with a name, or null when the inquiry has none.
     */
    public function question(string $name): ?Question
    {
        foreach ($this->questions as $question) {
            if ($question->name === $name) {
                return $question;
            }
        }

        return null;
    }
}
