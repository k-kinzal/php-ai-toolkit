<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Fake;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Http\Transport;
use DecisionsLint\Provider\Ollama\OllamaClient;

/**
 * Stands in for an Ollama server: reads a chat request with a `format` schema and answers in Ollama's response shape.
 */
final class FakeOllamaTransport implements Transport
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
    public function __construct(private ?AnswerScript $script = null, private string $model = 'llama3.1', private ?RequestBody $body = null, private ?ChatRequestReader $reader = null)
    {
    }

    /**
     * Answers every chat request from the script.
     *
     * @param list<HttpRequest> $requests
     * @return list<HttpResponse>
     * @throws LintException when a request is not a well-formed Ollama chat request
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
     * @throws LintException when the request is not a well-formed Ollama chat request
     */
    public function respond(HttpRequest $request): string
    {
        $body = $this->body ?? new RequestBody();
        $reader = $this->reader ?? new ChatRequestReader();
        $payload = $body->read($request, OllamaClient::PATH, 'Ollama');
        $payload->string('model');
        $answers = $reader->answers($reader->questions($payload->nested('format')), $reader->subject($payload), $this->script ?? new AnswerScript());

        return $body->encode([
            'model' => $this->model,
            'message' => ['role' => 'assistant', 'content' => $body->encode($answers)],
            'done' => true,
        ]);
    }
}
