<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\OpenAiCompatible;

use DecisionsLint\Decision\Decision;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Chat\ChatAnswerReader;
use DecisionsLint\Provider\Chat\ChatPrompt;
use DecisionsLint\Provider\Payload;

/**
 * Adapts any OpenAI-compatible chat completions server to the Decision interface.
 *
 * LocalAI, vLLM, llama.cpp and hosted gateways all serve this API. The
 * questions go into the prompt, the answer schema goes into `response_format`,
 * and the first choice's message is read back as typed answers.
 */
final class ChatCompletionsDecision implements Decision
{
    /**
     * Creates the adapter over a client, optionally with shared prompt and reader.
     */
    public function __construct(private ChatCompletionsClient $client, private ?ChatPrompt $prompt = null, private ?ChatAnswerReader $reader = null)
    {
    }

    /**
     * Answers every inquiry with one completion request each.
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
        foreach ($this->client->complete($requests) as $index => $response) {
            $verdicts[] = $this->verdict($response, $inquiries[$index]);
        }

        return $verdicts;
    }

    /**
     * Translates an inquiry into the messages and the answer schema of a completion request.
     *
     * @return array{messages: list<array{role: string, content: string}>, schema: array<string, mixed>}
     */
    public function request(Inquiry $inquiry): array
    {
        $prompt = $this->prompt ?? new ChatPrompt();

        return ['messages' => $prompt->messages($inquiry), 'schema' => $prompt->schema($inquiry)];
    }

    /**
     * Reads the first choice of a response into a verdict.
     *
     * @param array<string, mixed> $response
     * @throws LintException when the response has no choice or the message is not the expected JSON
     */
    public function verdict(array $response, Inquiry $inquiry): Verdict
    {
        $payload = new Payload($response, 'chat completions response');
        $choices = $payload->entries('choices');
        if ($choices === []) {
            throw new LintException('chat completions response: field "choices" is empty, so the model returned no answer. Check provider.model and the server logs.');
        }
        $content = $choices[0]->nested('message')->string('content');

        return ($this->reader ?? new ChatAnswerReader())->verdict($content, $payload->stringOr('model', 'chat'), $inquiry, 'The chat model');
    }
}
