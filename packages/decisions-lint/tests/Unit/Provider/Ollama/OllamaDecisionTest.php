<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Ollama;

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
use DecisionsLint\Provider\Fake\FakeOllamaTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Ollama\OllamaClient;
use DecisionsLint\Provider\Ollama\OllamaDecision;
use DecisionsLint\Provider\OpenAi\InputRenderer;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Ollama\OllamaDecision
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
 * @uses \DecisionsLint\Provider\Fake\FakeOllamaTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Ollama\OllamaClient
 * @uses \DecisionsLint\Provider\OpenAi\InputRenderer
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(OllamaDecision::class)]
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
#[UsesClass(FakeOllamaTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(OllamaClient::class)]
#[UsesClass(InputRenderer::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class OllamaDecisionTest extends TestCase
{
    public function testDecideRoundTripsEveryQuestionTypeThroughTheFakeServer(): void
    {
        $script = new AnswerScript(static fn (Question $question, array $subject): Answer => match ($question->type) {
            Question::PREDICATE => Answer::predicate(str_contains($subject['input'], 'sendMail') ? 0.1 : 0.9),
            Question::CHOICE => Answer::choice('leak', 0.8, ['leak' => 0.8]),
            default => Answer::score(1.0, 0.6, ['1' => 0.6]),
        });
        $transport = new FakeOllamaTransport($script, 'llama3.1:8b');
        $decision = new OllamaDecision(new OllamaClient(new JsonEndpoint($transport, ProviderSettings::defaults('ollama'), ''), 'llama3.1'), new ChatPrompt(), new ChatAnswerReader());
        $questions = [
            new Question('p', Question::PREDICATE, 'Fits?'),
            new Question('c', Question::CHOICE, 'Classify.', ['ok' => 'fine', 'leak' => 'bad']),
            new Question('s', Question::SCORE, 'Rate.', [], ['low', 'mid', 'high']),
        ];
        $verdicts = $decision->decide([new Inquiry(['name' => 'A::sendMail'], $questions), new Inquiry(['name' => 'A::analyze'], $questions)]);
        self::assertCount(2, $verdicts);
        self::assertSame('llama3.1:8b', $verdicts[0]->model);
        self::assertSame(0.1, $verdicts[0]->answer('p')->probability);
        self::assertSame(0.9, $verdicts[1]->answer('p')->probability);
        self::assertSame('leak', $verdicts[0]->answer('c')->choice);
        self::assertSame(1.0, $verdicts[0]->answer('s')->score);
        self::assertCount(2, $transport->requests);
    }

    public function testRequestCarriesTheMessagesAndTheSchema(): void
    {
        $decision = new OllamaDecision(new OllamaClient(new JsonEndpoint(new FakeOllamaTransport(), ProviderSettings::defaults('ollama'), ''), 'llama3.1'));
        $request = $decision->request(new Inquiry(['file' => 'a.php'], [new Question('p', Question::PREDICATE, 'P?')]));
        self::assertSame('system', $request['messages'][0]['role']);
        self::assertStringContainsString('### p', $request['messages'][1]['content']);
        self::assertSame(['p'], $request['format']['required']);
    }

    public function testVerdictRejectsAResponseWithoutAMessage(): void
    {
        $decision = new OllamaDecision(new OllamaClient(new JsonEndpoint(new FakeOllamaTransport(), ProviderSettings::defaults('ollama'), ''), 'llama3.1'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Ollama response: field "message" must be a mapping or list, but it is missing');
        $decision->verdict(['model' => 'llama3.1'], new Inquiry([], []));
    }
}
