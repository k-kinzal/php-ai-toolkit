<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\OpenAi;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeOpenAiTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\OpenAi\InputRenderer;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\OpenAi\OpenAiDecision;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\OpenAi\OpenAiDecision
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeOpenAiTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\OpenAi\InputRenderer
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiClient
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(OpenAiDecision::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(LintException::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeOpenAiTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(InputRenderer::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class OpenAiDecisionTest extends TestCase
{
    public function testDecideSendsOneRequestPerInquiryAndReadsEveryAnswer(): void
    {
        $script = new AnswerScript(static fn (Question $question, array $subject): Answer => match ($question->type) {
            Question::CHOICE => Answer::choice('b', 0.8, ['a' => 0.2, 'b' => 0.8]),
            Question::SCORE => Answer::score(1.1, 0.6, ['0' => 0.3, '1' => 0.3, '2' => 0.4]),
            default => Answer::predicate(str_contains($subject['input'], 'a.php') ? 0.9 : 0.1),
        });
        $transport = new FakeOpenAiTransport($script, 'gpt-6-luna');
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint($transport, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'), new InputRenderer());
        $questions = [
            new Question('p', Question::PREDICATE, 'P?'),
            new Question('c', Question::CHOICE, 'C?', ['a' => 'A', 'b' => 'B']),
            new Question('s', Question::SCORE, 'S?', [], ['low', 'mid', 'high']),
        ];
        $verdicts = $decision->decide([new Inquiry(['file' => 'a.php'], $questions), new Inquiry(['file' => 'b.php'], $questions)]);
        self::assertSame('gpt-6-luna', $verdicts[0]->model);
        self::assertSame(0.9, $verdicts[0]->answer('p')->probability);
        self::assertSame(0.1, $verdicts[1]->answer('p')->probability);
        self::assertSame('b', $verdicts[0]->answer('c')->choice);
        self::assertSame(['a' => 0.2, 'b' => 0.8], $verdicts[0]->answer('c')->probabilities);
        self::assertSame(1.1, $verdicts[0]->answer('s')->score);
        self::assertStringContainsString('"input":"## file\n\na.php\n"', $transport->requests[0]->body);
    }

    public function testVerdictTreatsRefusalsAndMissingAnswersAsRefusals(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new FakeOpenAiTransport(), ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        $verdict = $decision->verdict(['model' => 'gpt-6-luna', 'answers' => [['type' => 'refusal', 'name' => 'r']]], new Inquiry([], [new Question('r', Question::PREDICATE, 'R?'), new Question('missing', Question::PREDICATE, 'M?')]));
        self::assertSame(Answer::REFUSAL, $verdict->answer('r')->type);
        self::assertSame(Answer::REFUSAL, $verdict->answer('missing')->type);
    }

    public function testRequestRendersTheSubjectAndListsTheQuestions(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new FakeOpenAiTransport(), ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        $request = $decision->request(new Inquiry(['file' => 'a.php'], [new Question('x', Question::PREDICATE, 'X?')]));
        self::assertSame("## file\n\na.php\n", $request['input']);
        self::assertSame([['type' => 'predicate', 'name' => 'x', 'instructions' => 'X?']], $request['questions']);
    }

    public function testQuestionMapsChoicesAndLevelsToObjects(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new FakeOpenAiTransport(), ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        self::assertSame(['type' => 'choice', 'name' => 'c', 'instructions' => 'C?', 'choices' => [['value' => 'a', 'description' => 'A']]], $decision->question(new Question('c', Question::CHOICE, 'C?', ['a' => 'A'])));
        self::assertSame(['type' => 'score', 'name' => 's', 'instructions' => 'S?', 'levels' => [['label' => '0', 'description' => 'low'], ['label' => '1', 'description' => 'high']]], $decision->question(new Question('s', Question::SCORE, 'S?', [], ['low', 'high'])));
    }

    public function testVerdictRejectsAResponseWithoutTheAnswersList(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new FakeOpenAiTransport(), ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('OpenAI response: field "answers" must be a mapping or list, but it is missing');
        $decision->verdict([], new Inquiry([], []));
    }

    public function testAnswerReadsTheFieldOfEachType(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new FakeOpenAiTransport(), ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        self::assertSame(0.3, $decision->answer(new Payload(['probability' => 0.3], 'ctx'), new Question('p', Question::PREDICATE, 'P?'))->probability);
        self::assertSame('a', $decision->answer(new Payload(['choice' => 'a', 'confidence' => 1, 'probabilities' => [['value' => 'a', 'probability' => 1]]], 'ctx'), new Question('c', Question::CHOICE, 'C?'))->choice);
        self::assertSame(2.0, $decision->answer(new Payload(['score' => 2, 'confidence' => 1, 'probabilities' => []], 'ctx'), new Question('s', Question::SCORE, 'S?'))->score);
    }

    public function testDistributionAcceptsValuesOrLabels(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new FakeOpenAiTransport(), ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        self::assertSame(['a' => 0.5, 'b' => 0.5], $decision->distribution(new Payload(['probabilities' => [['value' => 'a', 'probability' => 0.5], ['label' => 'b', 'probability' => 0.5]]], 'ctx')));
    }
}
