<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Chat;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Provider\OpenAi\InputRenderer;

/**
 * Turns an inquiry into a chat conversation and the JSON schema the model's answer must follow.
 *
 * A generative chat model has no decision endpoint, so the questions are
 * written into the prompt and the answer is constrained to one JSON object
 * with one entry per question: a probability for a predicate, an option and a
 * confidence for a choice, a level and a confidence for a score.
 */
final class ChatPrompt
{
    /**
     * The instructions every conversation opens with.
     */
    public const SYSTEM = 'You are a code reviewer who answers fixed-form questions about PHP source. Read the source, then answer every question in the Questions section with one JSON object that follows the given schema and nothing else. Give calibrated numbers: a probability or confidence of 1.0 means certain, 0.5 means a coin flip. Judge only what the source shows.';

    /**
     * Creates a prompt builder, optionally with a shared renderer.
     */
    public function __construct(private ?InputRenderer $renderer = null)
    {
    }

    /**
     * Returns the system and user messages for an inquiry.
     *
     * @return list<array{role: string, content: string}>
     */
    public function messages(Inquiry $inquiry): array
    {
        $sections = [];
        foreach ($inquiry->questions as $question) {
            $sections[] = $this->describe($question);
        }
        $user = ($this->renderer ?? new InputRenderer())->render($inquiry->subject) . "\n## Questions\n\n" . implode("\n\n", $sections) . "\n";

        return [
            ['role' => 'system', 'content' => self::SYSTEM],
            ['role' => 'user', 'content' => $user],
        ];
    }

    /**
     * Writes one question for the prompt: its name, its text and the answers it allows.
     */
    public function describe(Question $question): string
    {
        $lines = [sprintf('### %s', $question->name), $question->instructions];
        if ($question->type === Question::CHOICE) {
            $lines[] = 'Answer with exactly one of these options in "choice":';
            foreach ($question->choices as $name => $description) {
                $lines[] = sprintf('- %s: %s', $name, $description);
            }
        } elseif ($question->type === Question::SCORE) {
            $lines[] = 'Answer with the number of the level that fits best in "score":';
            foreach ($question->levels as $index => $description) {
                $lines[] = sprintf('- %d: %s', $index, $description);
            }
        } else {
            $lines[] = 'Answer with the probability, from 0 to 1, that the statement holds in "probability".';
        }

        return implode("\n", $lines);
    }

    /**
     * Returns the JSON schema of the whole answer: one required object per question.
     *
     * @return array<string, mixed>
     */
    public function schema(Inquiry $inquiry): array
    {
        $properties = [];
        $required = [];
        foreach ($inquiry->questions as $question) {
            $properties[$question->name] = $this->answerSchema($question);
            $required[] = $question->name;
        }

        return ['type' => 'object', 'properties' => $properties, 'required' => $required, 'additionalProperties' => false];
    }

    /**
     * Returns the JSON schema of one answer.
     *
     * @return array<string, mixed>
     */
    public function answerSchema(Question $question): array
    {
        $unit = ['type' => 'number', 'minimum' => 0, 'maximum' => 1];
        $properties = match ($question->type) {
            Question::CHOICE => ['choice' => ['type' => 'string', 'enum' => array_keys($question->choices)], 'confidence' => $unit],
            Question::SCORE => ['score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => max(0, count($question->levels) - 1)], 'confidence' => $unit],
            default => ['probability' => $unit],
        };

        return ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false];
    }
}
