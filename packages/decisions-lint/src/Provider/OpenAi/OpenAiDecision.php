<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\OpenAi;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Decision;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Payload;

/**
 * Adapts the OpenAI Decisions API to the Decision interface.
 *
 * The subject sections are rendered into one text input. Choices become
 * `{value, description}` entries and score levels become `{label, description}`
 * entries. Answers are read back by name; a refusal becomes a refusal answer.
 */
final class OpenAiDecision implements Decision
{
    /**
     * Creates the adapter over a client.
     */
    public function __construct(private OpenAiClient $client, private ?InputRenderer $renderer = null)
    {
    }

    /**
     * Answers every inquiry with one Decisions request each.
     *
     * @param list<Inquiry> $inquiries
     * @return list<Verdict>
     * @throws LintException when the API rejects a request, cannot be reached, or answers in an unexpected shape
     */
    public function decide(array $inquiries): array
    {
        $requests = [];
        foreach ($inquiries as $inquiry) {
            $requests[] = $this->request($inquiry);
        }
        $verdicts = [];
        foreach ($this->client->decisions($requests) as $index => $response) {
            $verdicts[] = $this->verdict($response, $inquiries[$index]);
        }

        return $verdicts;
    }

    /**
     * Translates an inquiry into the input and questions of a Decisions request.
     *
     * @return array{input: string, questions: list<array<string, mixed>>}
     */
    public function request(Inquiry $inquiry): array
    {
        $questions = [];
        foreach ($inquiry->questions as $question) {
            $questions[] = $this->question($question);
        }

        return ['input' => ($this->renderer ?? new InputRenderer())->render($inquiry->subject), 'questions' => $questions];
    }

    /**
     * Translates one question into its Decisions form.
     *
     * @return array<string, mixed>
     */
    public function question(Question $question): array
    {
        $base = ['type' => $question->type, 'name' => $question->name, 'instructions' => $question->instructions];
        if ($question->type === Question::CHOICE) {
            $choices = [];
            foreach ($question->choices as $value => $description) {
                $choices[] = ['value' => $value, 'description' => $description];
            }

            return $base + ['choices' => $choices];
        }
        if ($question->type === Question::SCORE) {
            $levels = [];
            foreach ($question->levels as $index => $description) {
                $levels[] = ['label' => (string) $index, 'description' => $description];
            }

            return $base + ['levels' => $levels];
        }

        return $base;
    }

    /**
     * Reads the answers of a response into a verdict.
     *
     * @param array<string, mixed> $response
     * @throws LintException when the response lacks the answers list or an answer field
     */
    public function verdict(array $response, Inquiry $inquiry): Verdict
    {
        $payload = new Payload($response, 'OpenAI response');
        $byName = [];
        foreach ($payload->entries('answers') as $entry) {
            $byName[$entry->string('name')] = $entry;
        }
        $result = [];
        foreach ($inquiry->questions as $question) {
            $entry = $byName[$question->name] ?? null;
            $result[$question->name] = $entry === null || $entry->stringOr('type', '') === Answer::REFUSAL ? Answer::refusal() : $this->answer($entry, $question);
        }

        return new Verdict($payload->stringOr('model', 'openai'), $result);
    }

    /**
     * Reads one answer in the shape the question's type promises.
     *
     * @throws LintException when a field is missing or malformed
     */
    public function answer(Payload $answer, Question $question): Answer
    {
        return match ($question->type) {
            Question::CHOICE => Answer::choice($answer->string('choice'), $answer->float('confidence'), $this->distribution($answer)),
            Question::SCORE => Answer::score($answer->float('score'), $answer->float('confidence'), $this->distribution($answer)),
            default => Answer::predicate($answer->float('probability')),
        };
    }

    /**
     * Reads a `probabilities` list of `{value, probability}` entries into a map.
     *
     * @return array<array-key, float>
     * @throws LintException when an entry lacks its value or probability
     */
    public function distribution(Payload $answer): array
    {
        $probabilities = [];
        foreach ($answer->entries('probabilities') as $entry) {
            $value = $entry->has('value') ? $entry->string('value') : $entry->string('label');
            $probabilities[$value] = $entry->float('probability');
        }

        return $probabilities;
    }
}
