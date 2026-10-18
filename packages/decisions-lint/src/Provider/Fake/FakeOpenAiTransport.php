<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\Payload;

/**
 * Stands in for the OpenAI Decisions API: reads a Decisions request and answers in its response shape.
 */
final class FakeOpenAiTransport implements Transport
{
    /**
     * Every request received so far, in order.
     *
     * @var list<HttpRequest>
     */
    public array $requests = [];

    /**
     * Creates the fake with a script and the model it reports.
     */
    public function __construct(private ?AnswerScript $script = null, private string $model = 'gpt-6-luna', private ?RequestBody $body = null)
    {
    }

    /**
     * Answers every Decisions request from the script.
     *
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when a request is not a well-formed Decisions request
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
     * @throws LintException when the request is not a well-formed Decisions request
     */
    public function respond(HttpRequest $request): string
    {
        $body = $this->body ?? new RequestBody();
        $payload = $body->read($request, OpenAiClient::PATH, 'OpenAI');
        $payload->string('model');
        $subject = ['input' => $payload->string('input')];
        $script = $this->script ?? new AnswerScript();
        $answers = [];
        foreach ($payload->entries('questions') as $definition) {
            $question = $this->question($definition);
            $answers[] = $this->encode($script->answer($question, $subject), $question);
        }

        return $body->encode(['model' => $this->model, 'answers' => $answers]);
    }

    /**
     * Rebuilds the question a Decisions request describes.
     *
     * @throws LintException when the question lacks its name, type or instructions
     */
    public function question(Payload $definition): Question
    {
        $name = $definition->string('name');
        $type = $definition->string('type');
        $instructions = $definition->string('instructions');
        if ($type === Question::CHOICE) {
            $choices = [];
            foreach ($definition->entries('choices') as $choice) {
                $choices[$choice->string('value')] = $choice->stringOr('description', '');
            }

            return new Question($name, Question::CHOICE, $instructions, $choices);
        }
        if ($type === Question::SCORE) {
            $levels = [];
            foreach ($definition->entries('levels') as $level) {
                $levels[] = $level->stringOr('description', '');
            }

            return new Question($name, Question::SCORE, $instructions, [], $levels);
        }

        return new Question($name, Question::PREDICATE, $instructions);
    }

    /**
     * Writes an answer the way the Decisions API does.
     *
     * @return array<string, mixed>
     */
    public function encode(Answer $answer, Question $question): array
    {
        $probabilities = [];
        foreach ($answer->probabilities as $value => $probability) {
            $probabilities[] = ['value' => (string) $value, 'label' => (string) $value, 'probability' => $probability];
        }

        return match ($question->type) {
            Question::CHOICE => ['type' => 'choice', 'name' => $question->name, 'choice' => $answer->choice, 'confidence' => $answer->confidence, 'probabilities' => $probabilities],
            Question::SCORE => ['type' => 'score', 'name' => $question->name, 'score' => $answer->score, 'confidence' => $answer->confidence, 'probabilities' => $probabilities],
            default => ['type' => 'predicate', 'name' => $question->name, 'probability' => $answer->probability],
        };
    }
}
