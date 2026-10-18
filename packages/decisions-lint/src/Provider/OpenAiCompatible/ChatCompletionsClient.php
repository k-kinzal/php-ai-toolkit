<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\OpenAiCompatible;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\JsonEndpoint;

/**
 * The OpenAI chat completions API as LocalAI, vLLM, llama.cpp and similar servers implement it: POST /v1/chat/completions.
 *
 * A request carries the `model`, the `messages`, a zero temperature and a
 * `response_format` of type `json_schema` with the answer schema. The
 * response carries `choices`, the first of which holds the assistant message.
 */
final class ChatCompletionsClient
{
    /**
     * The path of the chat completions endpoint.
     */
    public const PATH = '/v1/chat/completions';

    /**
     * Creates a client over a configured endpoint and a model identifier.
     */
    public function __construct(private JsonEndpoint $endpoint, private string $model)
    {
    }

    /**
     * Sends one completion request per conversation and returns the decoded responses in order.
     *
     * @param list<array{messages: list<array{role: string, content: string}>, schema: array<string, mixed>}> $requests
     * @return list<array<string, mixed>>
     * @throws LintException when the server rejects a request or cannot be reached
     */
    public function complete(array $requests): array
    {
        $payloads = [];
        foreach ($requests as $request) {
            $payloads[] = [
                'model' => $this->model,
                'messages' => $request['messages'],
                'temperature' => 0,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => ['name' => 'decisions', 'strict' => true, 'schema' => $request['schema']],
                ],
            ];
        }

        return $this->endpoint->post(self::PATH, $payloads);
    }
}
