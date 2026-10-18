<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\OpenAi;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\JsonEndpoint;

/**
 * The OpenAI Decisions API: POST /v1/decisions.
 *
 * A request carries a text `input` and a `questions` list whose entries are
 * `predicate`, `choice` or `score` questions with a `name`. The response
 * carries an `answers` list with the same names, or a `refusal` entry.
 */
final class OpenAiClient
{
    /**
     * The path of the Decisions endpoint.
     */
    public const PATH = '/v1/decisions';

    /**
     * Creates a client over a configured endpoint and a model identifier.
     */
    public function __construct(private JsonEndpoint $endpoint, private string $model)
    {
    }

    /**
     * Sends one Decisions request per input and returns the decoded responses in order.
     *
     * @param list<array{input: string, questions: list<array<string, mixed>>}> $requests
     * @return list<array<string, mixed>>
     * @throws LintException when the API rejects a request or cannot be reached
     */
    public function decisions(array $requests): array
    {
        $payloads = [];
        foreach ($requests as $request) {
            $payloads[] = ['model' => $this->model, 'input' => $request['input'], 'questions' => $request['questions']];
        }

        return $this->endpoint->post(self::PATH, $payloads);
    }
}
