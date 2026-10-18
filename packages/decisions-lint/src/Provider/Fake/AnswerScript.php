<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use Closure;
use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;

/**
 * Decides what a fake provider answers to each question about each subject.
 *
 * Without a closure every predicate holds with certainty, every choice picks
 * its first option and every score lands on its top level, so a fake run
 * reports no violation of the shipped rules. A closure receives the question
 * and the subject sections and returns the answer, or null for the default.
 */
final class AnswerScript
{
    /**
     * Creates a script, optionally with a closure that chooses the answers.
     *
     * @param ?Closure(Question, array<string, string>): ?Answer $decide
     */
    public function __construct(private ?Closure $decide = null)
    {
    }

    /**
     * Returns the answer to a question about a subject.
     *
     * @param array<string, string> $subject
     */
    public function answer(Question $question, array $subject): Answer
    {
        $answer = $this->decide === null ? null : ($this->decide)($question, $subject);

        return $answer ?? $this->agreeable($question);
    }

    /**
     * Returns the answer that never violates a well-formed rule: yes, the first option, or the top level.
     */
    public function agreeable(Question $question): Answer
    {
        if ($question->type === Question::CHOICE) {
            $first = array_key_first($question->choices);
            $choice = $first === null ? '' : sprintf('%s', $first);

            return Answer::choice($choice, 1.0, [$choice => 1.0]);
        }
        if ($question->type === Question::SCORE) {
            $top = max(0, count($question->levels) - 1);

            return Answer::score((float) $top, 1.0, [(string) $top => 1.0]);
        }

        return Answer::predicate(1.0);
    }
}
