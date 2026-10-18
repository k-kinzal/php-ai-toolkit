<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Payload;

/**
 * What the two chat fakes share: rebuilding the questions from the answer schema and writing the JSON answer a chat model would.
 *
 * The prompt text is free-form, so the fakes read the questions from the
 * schema instead: each property is a question whose type follows from its
 * fields, and whose options or level count follow from the enum or maximum.
 */
final class ChatRequestReader
{
    /**
     * Returns the user message of a chat request as the subject the script sees.
     *
     * @return array<string, string>
     * @throws LintException when the request has no messages
     */
    public function subject(Payload $payload): array
    {
        $content = '';
        foreach ($payload->entries('messages') as $message) {
            if ($message->stringOr('role', '') === 'user') {
                $content = $message->stringOr('content', '');
            }
        }

        return ['input' => $content];
    }

    /**
     * Rebuilds the questions an answer schema describes.
     *
     * @return list<Question>
     * @throws LintException when the schema lacks its properties
     */
    public function questions(Payload $schema): array
    {
        $questions = [];
        $properties = $schema->nested('properties');
        foreach ($properties->keys() as $name) {
            $fields = $properties->nested($name)->nested('properties');
            if ($fields->has('choice')) {
                $choices = [];
                foreach ($fields->nested('choice')->nested('enum')->keys() as $index) {
                    $choices[$fields->nested('choice')->nested('enum')->string($index)] = '';
                }
                $questions[] = new Question($name, Question::CHOICE, '', $choices);
            } elseif ($fields->has('score')) {
                $questions[] = new Question($name, Question::SCORE, '', [], array_fill(0, (int) $fields->nested('score')->float('maximum') + 1, ''));
            } else {
                $questions[] = new Question($name, Question::PREDICATE, '');
            }
        }

        return $questions;
    }

    /**
     * Writes the JSON object a chat model would answer with.
     *
     * @param list<Question> $questions
     * @param array<string, string> $subject
     * @return array<string, array<string, float|int|string>>
     */
    public function answers(array $questions, array $subject, AnswerScript $script): array
    {
        $answers = [];
        foreach ($questions as $question) {
            $answers[$question->name] = $this->encode($script->answer($question, $subject), $question);
        }

        return $answers;
    }

    /**
     * Writes one answer in the shape the answer schema asks for.
     *
     * @return array<string, float|int|string>
     */
    public function encode(Answer $answer, Question $question): array
    {
        return match ($question->type) {
            Question::CHOICE => ['choice' => $answer->choice, 'confidence' => $answer->confidence],
            Question::SCORE => ['score' => (int) round($answer->score), 'confidence' => $answer->confidence],
            default => ['probability' => $answer->probability],
        };
    }
}
