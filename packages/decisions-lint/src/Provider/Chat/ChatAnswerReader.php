<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Chat;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Payload;
use JsonException;

/**
 * Reads the JSON object a chat model wrote back into a verdict.
 *
 * The model's text may be wrapped in a code fence; the fence is removed. An
 * answer that is missing or that names an option the question does not have
 * becomes a refusal, so the linter reports nothing for it.
 */
final class ChatAnswerReader
{
    /**
     * Turns the model's text into a verdict for an inquiry.
     *
     * @param string $content the assistant message
     * @param string $model the model that answered
     * @throws LintException when the text is not a JSON object
     */
    public function verdict(string $content, string $model, Inquiry $inquiry, string $provider): Verdict
    {
        $payload = new Payload($this->decode($content, $provider), $provider . ' answer');
        $answers = [];
        foreach ($inquiry->questions as $question) {
            $answers[$question->name] = $payload->has($question->name) ? $this->answer($payload->nested($question->name), $question) : Answer::refusal();
        }

        return new Verdict($model, $answers);
    }

    /**
     * Decodes the assistant message, stripping a Markdown code fence when the model added one.
     *
     * @return array<array-key, mixed>
     * @throws LintException when the text is not a JSON object
     */
    public function decode(string $content, string $provider): array
    {
        $text = trim($content);
        if (preg_match('/^```[a-zA-Z]*\s*(.*?)\s*```$/s', $text, $matches) === 1) {
            $text = $matches[1];
        }
        try {
            $decoded = json_decode($text, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LintException(sprintf('%s answered with text that is not JSON: %s. Use a model that supports structured JSON output, or set provider.model to one that does.', $provider, $this->excerpt($text)), 0, $exception);
        }
        if (!is_array($decoded)) {
            throw new LintException(sprintf('%s answered with JSON that is not an object: %s. Use a model that follows the answer schema.', $provider, $this->excerpt($text)));
        }

        return $decoded;
    }

    /**
     * Reads one answer in the shape the question's type asked for.
     *
     * @throws LintException when a field is missing or malformed
     */
    public function answer(Payload $answer, Question $question): Answer
    {
        if ($question->type === Question::CHOICE) {
            $choice = $answer->string('choice');
            if (!array_key_exists($choice, $question->choices)) {
                return Answer::refusal();
            }
            $confidence = $this->unit($answer->float('confidence'));

            return Answer::choice($choice, $confidence, [$choice => $confidence]);
        }
        if ($question->type === Question::SCORE) {
            $score = min(max(0.0, $answer->float('score')), (float) max(0, count($question->levels) - 1));
            $confidence = $this->unit($answer->float('confidence'));

            return Answer::score($score, $confidence, [(string) (int) round($score) => $confidence]);
        }

        return Answer::predicate($this->unit($answer->float('probability')));
    }

    /**
     * Clamps a number into the unit interval.
     */
    public function unit(float $value): float
    {
        return min(1.0, max(0.0, $value));
    }

    /**
     * Returns the start of a text for a message.
     */
    public function excerpt(string $text): string
    {
        $flat = trim((string) preg_replace('/\s+/', ' ', $text));

        return strlen($flat) > 120 ? substr($flat, 0, 120) . '…' : $flat;
    }
}
