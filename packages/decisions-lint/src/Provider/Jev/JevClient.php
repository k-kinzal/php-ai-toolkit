<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Jev;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\JsonEndpoint;

/**
 * The TypeSafe AI System One endpoint that runs Jev: POST /v1/systemone.
 *
 * A request carries a `state` and a `questions` object keyed by question id,
 * each question being `noul`, `choice` or `score`. The response carries an
 * `answers` object with the same keys, the `model` version and token `usage`.
 */
final class JevClient
{
    /**
     * The path of the System One endpoint.
     */
    public const PATH = '/v1/systemone';

    /**
     * Creates a client over a configured endpoint and a model identifier.
     */
    public function __construct(private JsonEndpoint $endpoint, private string $model)
    {
    }

    /**
     * Sends one System One request per state and returns the decoded responses in order.
     *
     * @param list<array{state: array<string, string>|string, questions: array<string, array<string, mixed>>}> $requests
     * @return list<array<string, mixed>>
     * @throws LintException when the API rejects a request or cannot be reached
     */
    public function systemOne(array $requests): array
    {
        $payloads = [];
        foreach ($requests as $request) {
            $payloads[] = ['model' => $this->model, 'state' => $request['state'], 'questions' => $request['questions']];
        }

        return $this->endpoint->post(self::PATH, $payloads);
    }
}
