<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Jev;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Decision;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Payload;

/**
 * Adapts Jev to the Decision interface.
 *
 * A predicate becomes a `noul`, a choice keeps its options as `criteria`, and a
 * score lists its levels as `criteria`. The subject sections become the state
 * object, so the model sees each section under its name.
 */
final class JevDecision implements Decision
{
    /**
     * Creates the adapter over a client.
     */
    public function __construct(private JevClient $client)
    {
    }

    /**
     * Answers every inquiry with one System One request each.
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
        foreach ($this->client->systemOne($requests) as $index => $response) {
            $verdicts[] = $this->verdict($response, $inquiries[$index]);
        }

        return $verdicts;
    }

    /**
     * Translates an inquiry into the state and questions of a System One request.
     *
     * @return array{state: array<string, string>, questions: array<string, array<string, mixed>>}
     */
    public function request(Inquiry $inquiry): array
    {
        $questions = [];
        foreach ($inquiry->questions as $question) {
            $questions[$question->name] = $this->question($question);
        }

        return ['state' => $inquiry->subject, 'questions' => $questions];
    }

    /**
     * Translates one question into its Jev form.
     *
     * @return array<string, mixed>
     */
    public function question(Question $question): array
    {
        return match ($question->type) {
            Question::CHOICE => ['type' => 'choice', 'instructions' => $question->instructions, 'criteria' => $question->choices],
            Question::SCORE => ['type' => 'score', 'instructions' => $question->instructions, 'criteria' => $question->levels],
            default => ['type' => 'noul', 'instructions' => $question->instructions],
        };
    }

    /**
     * Reads the answers of a response into a verdict.
     *
     * @param array<string, mixed> $response
     * @throws LintException when the response lacks an answer or an answer field
     */
    public function verdict(array $response, Inquiry $inquiry): Verdict
    {
        $payload = new Payload($response, 'Jev response');
        $answers = $payload->nested('answers');
        $result = [];
        foreach ($inquiry->questions as $question) {
            $result[$question->name] = $answers->has($question->name) ? $this->answer($answers->nested($question->name), $question) : Answer::refusal();
        }

        return new Verdict($payload->stringOr('model', 'jev'), $result);
    }

    /**
     * Reads one answer in the shape the question's type promises.
     *
     * @throws LintException when a field is missing or malformed
     */
    public function answer(Payload $answer, Question $question): Answer
    {
        return match ($question->type) {
            Question::CHOICE => Answer::choice($answer->string('choice'), $answer->float('confidence'), $answer->floats('probabilities')),
            Question::SCORE => Answer::score($answer->float('score'), $answer->float('confidence'), $answer->floats('probabilities')),
            default => Answer::predicate($answer->float('noul')),
        };
    }
}
