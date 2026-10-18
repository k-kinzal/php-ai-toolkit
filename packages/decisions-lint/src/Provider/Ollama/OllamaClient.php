<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Ollama;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\JsonEndpoint;

/**
 * The Ollama chat endpoint: POST /api/chat with a JSON schema in `format`.
 *
 * A request carries the `model`, the `messages`, `stream: false`, the answer
 * schema as `format` and a zero temperature. The response carries the
 * assistant `message` whose `content` is the JSON text.
 */
final class OllamaClient
{
    /**
     * The path of the chat endpoint.
     */
    public const PATH = '/api/chat';

    /**
     * Creates a client over a configured endpoint and a model identifier.
     */
    public function __construct(private JsonEndpoint $endpoint, private string $model)
    {
    }

    /**
     * Sends one chat request per conversation and returns the decoded responses in order.
     *
     * @param list<array{messages: list<array{role: string, content: string}>, format: array<string, mixed>}> $requests
     * @return list<array<string, mixed>>
     * @throws LintException when the server rejects a request or cannot be reached
     */
    public function chat(array $requests): array
    {
        $payloads = [];
        foreach ($requests as $request) {
            $payloads[] = [
                'model' => $this->model,
                'messages' => $request['messages'],
                'stream' => false,
                'format' => $request['format'],
                'options' => ['temperature' => 0],
            ];
        }

        return $this->endpoint->post(self::PATH, $payloads);
    }
}
