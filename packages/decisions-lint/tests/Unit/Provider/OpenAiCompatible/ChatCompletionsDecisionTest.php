<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\OpenAiCompatible;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Chat\ChatAnswerReader;
use DecisionsLint\Provider\Chat\ChatPrompt;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\ChatRequestReader;
use DecisionsLint\Provider\Fake\FakeChatCompletionsTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\OpenAi\InputRenderer;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Chat\ChatAnswerReader
 * @uses \DecisionsLint\Provider\Chat\ChatPrompt
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\ChatRequestReader
 * @uses \DecisionsLint\Provider\Fake\FakeChatCompletionsTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\OpenAi\InputRenderer
 * @uses \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(ChatCompletionsDecision::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(ChatAnswerReader::class)]
#[UsesClass(ChatPrompt::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(ChatRequestReader::class)]
#[UsesClass(FakeChatCompletionsTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(InputRenderer::class)]
#[UsesClass(ChatCompletionsClient::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class ChatCompletionsDecisionTest extends TestCase
{
    public function testDecideRoundTripsEveryQuestionTypeThroughTheFakeServer(): void
    {
        $script = new AnswerScript(static fn (Question $question): ?Answer => $question->type === Question::CHOICE ? Answer::choice('infra', 0.7, ['infra' => 0.7]) : null);
        $transport = new FakeChatCompletionsTransport($script, 'local-model');
        $decision = new ChatCompletionsDecision(new ChatCompletionsClient(new JsonEndpoint($transport, ProviderSettings::defaults('localai'), ''), 'gpt-4'), new ChatPrompt(), new ChatAnswerReader());
        $verdicts = $decision->decide([new Inquiry(['name' => 'A::f'], [
            new Question('p', Question::PREDICATE, 'Fits?'),
            new Question('c', Question::CHOICE, 'Classify.', ['ok' => 'fine', 'infra' => 'bad']),
            new Question('s', Question::SCORE, 'Rate.', [], ['low', 'high']),
        ])]);
        self::assertSame('local-model', $verdicts[0]->model);
        self::assertSame(1.0, $verdicts[0]->answer('p')->probability);
        self::assertSame('infra', $verdicts[0]->answer('c')->choice);
        self::assertSame(0.7, $verdicts[0]->answer('c')->confidence);
        self::assertSame(1.0, $verdicts[0]->answer('s')->score);
        self::assertStringContainsString('"response_format":{"type":"json_schema"', $transport->requests[0]->body);
    }

    public function testRequestCarriesTheMessagesAndTheSchema(): void
    {
        $decision = new ChatCompletionsDecision(new ChatCompletionsClient(new JsonEndpoint(new FakeChatCompletionsTransport(), ProviderSettings::defaults('localai'), ''), 'gpt-4'));
        $request = $decision->request(new Inquiry(['file' => 'a.php'], [new Question('p', Question::PREDICATE, 'P?')]));
        self::assertCount(2, $request['messages']);
        self::assertSame(['p'], $request['schema']['required']);
    }

    public function testVerdictRejectsAResponseWithoutChoices(): void
    {
        $decision = new ChatCompletionsDecision(new ChatCompletionsClient(new JsonEndpoint(new FakeChatCompletionsTransport(), ProviderSettings::defaults('localai'), ''), 'gpt-4'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('chat completions response: field "choices" is empty, so the model returned no answer. Check provider.model and the server logs.');
        $decision->verdict(['model' => 'gpt-4', 'choices' => []], new Inquiry([], []));
    }
}
