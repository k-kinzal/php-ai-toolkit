<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\Ollama;

use DecisionsLint\Decision\Decision;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Chat\ChatAnswerReader;
use DecisionsLint\Provider\Chat\ChatPrompt;
use DecisionsLint\Provider\Payload;

/**
 * Adapts a chat model served by Ollama to the Decision interface.
 *
 * The questions go into the prompt, the answer schema goes into `format`, and
 * the assistant's JSON is read back as typed answers.
 */
final class OllamaDecision implements Decision
{
    /**
     * Creates the adapter over a client, optionally with shared prompt and reader.
     */
    public function __construct(private OllamaClient $client, private ?ChatPrompt $prompt = null, private ?ChatAnswerReader $reader = null)
    {
    }

    /**
     * Answers every inquiry with one chat request each.
     *
     * @param list<Inquiry> $inquiries
     * @return list<Verdict>
     * @throws LintException when the server rejects a request, cannot be reached, or answers in an unexpected shape
     */
    public function decide(array $inquiries): array
    {
        $requests = [];
        foreach ($inquiries as $inquiry) {
            $requests[] = $this->request($inquiry);
        }
        $verdicts = [];
        foreach ($this->client->chat($requests) as $index => $response) {
            $verdicts[] = $this->verdict($response, $inquiries[$index]);
        }

        return $verdicts;
    }

    /**
     * Translates an inquiry into the messages and the answer schema of a chat request.
     *
     * @return array{messages: list<array{role: string, content: string}>, format: array<string, mixed>}
     */
    public function request(Inquiry $inquiry): array
    {
        $prompt = $this->prompt ?? new ChatPrompt();

        return ['messages' => $prompt->messages($inquiry), 'format' => $prompt->schema($inquiry)];
    }

    /**
     * Reads the assistant message of a response into a verdict.
     *
     * @param array<string, mixed> $response
     * @throws LintException when the response lacks the message or the message is not the expected JSON
     */
    public function verdict(array $response, Inquiry $inquiry): Verdict
    {
        $payload = new Payload($response, 'Ollama response');
        $content = $payload->nested('message')->string('content');

        return ($this->reader ?? new ChatAnswerReader())->verdict($content, $payload->stringOr('model', 'ollama'), $inquiry, 'Ollama');
    }
}
