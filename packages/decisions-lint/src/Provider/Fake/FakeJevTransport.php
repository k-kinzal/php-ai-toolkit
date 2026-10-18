<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Payload;

/**
 * Stands in for the TypeSafe System One endpoint: reads a Jev request and answers in Jev's response shape.
 */
final class FakeJevTransport implements Transport
{
    /**
     * Every request received so far, in order.
     *
     * @var list<HttpRequest>
     */
    public array $requests = [];

    /**
     * Creates the fake with a script and the model version it reports.
     */
    public function __construct(private ?AnswerScript $script = null, private string $model = 'jev-1.13.0', private ?RequestBody $body = null)
    {
    }

    /**
     * Answers every System One request from the script.
     *
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when a request is not a well-formed System One request
     */
    public function send(array $requests): array
    {
        $responses = [];
        foreach ($requests as $request) {
            $this->requests[] = $request;
            $responses[] = new HttpResponse(200, $this->respond($request));
        }

        return $responses;
    }

    /**
     * Builds the response body for one request.
     *
     * @throws LintException when the request is not a well-formed System One request
     */
    public function respond(HttpRequest $request): string
    {
        $body = $this->body ?? new RequestBody();
        $payload = $body->read($request, JevClient::PATH, 'Jev');
        $payload->string('model');
        $state = $body->strings($payload, 'state');
        $questions = $payload->nested('questions');
        $script = $this->script ?? new AnswerScript();
        $answers = [];
        foreach ($questions->keys() as $name) {
            $question = $this->question($name, $questions->nested($name));
            $answers[$name] = $this->encode($script->answer($question, $state), $question);
        }

        return $body->encode(['model' => $this->model, 'answers' => $answers, 'usage' => ['input_tokens' => 0, 'output_tokens' => 0]]);
    }

    /**
     * Rebuilds the question a Jev request describes.
     *
     * @throws LintException when the question lacks its type or instructions
     */
    public function question(string $name, Payload $definition): Question
    {
        $type = $definition->string('type');
        $instructions = $definition->string('instructions');
        if ($type === 'choice') {
            $choices = [];
            $criteria = $definition->nested('criteria');
            foreach ($criteria->keys() as $option) {
                $choices[$option] = $criteria->stringOr($option, '');
            }

            return new Question($name, Question::CHOICE, $instructions, $choices);
        }
        if ($type === 'score') {
            $levels = [];
            $criteria = $definition->nested('criteria');
            foreach ($criteria->keys() as $index) {
                $levels[] = $criteria->stringOr($index, '');
            }

            return new Question($name, Question::SCORE, $instructions, [], $levels);
        }

        return new Question($name, Question::PREDICATE, $instructions);
    }

    /**
     * Writes an answer the way Jev does.
     *
     * @return array<string, mixed>
     */
    public function encode(Answer $answer, Question $question): array
    {
        return match ($question->type) {
            Question::CHOICE => ['type' => 'choice', 'choice' => $answer->choice, 'confidence' => $answer->confidence, 'probabilities' => $answer->probabilities],
            Question::SCORE => ['type' => 'score', 'score' => $answer->score, 'confidence' => $answer->confidence, 'legend' => $question->levels, 'probabilities' => $answer->probabilities],
            default => ['type' => 'noul', 'noul' => $answer->probability],
        };
    }
}
