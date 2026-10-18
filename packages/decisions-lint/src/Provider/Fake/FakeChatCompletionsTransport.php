<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient;

/**
 * Stands in for an OpenAI-compatible chat completions server such as LocalAI: reads the `response_format` schema and answers in the completions shape.
 */
final class FakeChatCompletionsTransport implements Transport
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
    public function __construct(private ?AnswerScript $script = null, private string $model = 'gpt-4', private ?RequestBody $body = null, private ?ChatRequestReader $reader = null)
    {
    }

    /**
     * Answers every completion request from the script.
     *
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when a request is not a well-formed chat completions request
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
     * @throws LintException when the request is not a well-formed chat completions request
     */
    public function respond(HttpRequest $request): string
    {
        $body = $this->body ?? new RequestBody();
        $reader = $this->reader ?? new ChatRequestReader();
        $payload = $body->read($request, ChatCompletionsClient::PATH, 'Chat completions');
        $payload->string('model');
        $schema = $payload->nested('response_format')->nested('json_schema')->nested('schema');
        $answers = $reader->answers($reader->questions($schema), $reader->subject($payload), $this->script ?? new AnswerScript());

        return $body->encode([
            'model' => $this->model,
            'choices' => [['index' => 0, 'finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => $body->encode($answers)]]],
        ]);
    }
}
