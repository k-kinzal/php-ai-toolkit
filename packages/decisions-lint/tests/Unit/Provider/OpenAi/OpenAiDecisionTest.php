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
use DecisionsLint\Http\Transport;
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
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(InputRenderer::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderSettings::class)]
final class OpenAiDecisionTest extends TestCase
{
    public function testDecideSendsOneRequestPerInquiryAndReadsEveryAnswer(): void
    {
        $transport = new class () implements Transport {
            /** @var list<HttpRequest> */
            public array $sent = [];
            public function send(array $requests): array
            {
                $this->sent = $requests;

                return [new HttpResponse(200, '{"model":"gpt-6-luna","answers":[{"type":"predicate","name":"p","probability":0.9},{"type":"choice","name":"c","choice":"b","confidence":0.8,"probabilities":[{"value":"a","probability":0.2},{"value":"b","probability":0.8}]},{"type":"score","name":"s","score":1.1,"confidence":0.6,"probabilities":[{"value":"0","label":"0","probability":0.3},{"value":"1","label":"1","probability":0.3},{"value":"2","label":"2","probability":0.4}]},{"type":"refusal","name":"r"}]}')];
            }
        };
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint($transport, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'), new InputRenderer());
        $inquiry = new Inquiry(['file' => 'a.php'], [
            new Question('p', Question::PREDICATE, 'P?'),
            new Question('c', Question::CHOICE, 'C?', ['a' => 'A', 'b' => 'B']),
            new Question('s', Question::SCORE, 'S?', [], ['low', 'mid', 'high']),
            new Question('r', Question::PREDICATE, 'R?'),
            new Question('missing', Question::PREDICATE, 'M?'),
        ]);
        $verdicts = $decision->decide([$inquiry]);
        self::assertSame('gpt-6-luna', $verdicts[0]->model);
        self::assertSame(0.9, $verdicts[0]->answer('p')->probability);
        self::assertSame('b', $verdicts[0]->answer('c')->choice);
        self::assertSame(['a' => 0.2, 'b' => 0.8], $verdicts[0]->answer('c')->probabilities);
        self::assertSame(1.1, $verdicts[0]->answer('s')->score);
        self::assertSame(Answer::REFUSAL, $verdicts[0]->answer('r')->type);
        self::assertSame(Answer::REFUSAL, $verdicts[0]->answer('missing')->type);
        self::assertStringContainsString('"input":"## file\n\na.php\n"', $transport->sent[0]->body);
    }

    public function testRequestRendersTheSubjectAndListsTheQuestions(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        }, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        $request = $decision->request(new Inquiry(['file' => 'a.php'], [new Question('x', Question::PREDICATE, 'X?')]));
        self::assertSame("## file\n\na.php\n", $request['input']);
        self::assertSame([['type' => 'predicate', 'name' => 'x', 'instructions' => 'X?']], $request['questions']);
    }

    public function testQuestionMapsChoicesAndLevelsToObjects(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        }, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        self::assertSame(['type' => 'choice', 'name' => 'c', 'instructions' => 'C?', 'choices' => [['value' => 'a', 'description' => 'A']]], $decision->question(new Question('c', Question::CHOICE, 'C?', ['a' => 'A'])));
        self::assertSame(['type' => 'score', 'name' => 's', 'instructions' => 'S?', 'levels' => [['label' => '0', 'description' => 'low'], ['label' => '1', 'description' => 'high']]], $decision->question(new Question('s', Question::SCORE, 'S?', [], ['low', 'high'])));
    }

    public function testVerdictRejectsAResponseWithoutTheAnswersList(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        }, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('OpenAI response: field "answers" must be a mapping or list, but it is missing');
        $decision->verdict([], new Inquiry([], []));
    }

    public function testAnswerReadsTheFieldOfEachType(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        }, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        self::assertSame(0.3, $decision->answer(new Payload(['probability' => 0.3], 'ctx'), new Question('p', Question::PREDICATE, 'P?'))->probability);
        self::assertSame('a', $decision->answer(new Payload(['choice' => 'a', 'confidence' => 1, 'probabilities' => [['value' => 'a', 'probability' => 1]]], 'ctx'), new Question('c', Question::CHOICE, 'C?'))->choice);
        self::assertSame(2.0, $decision->answer(new Payload(['score' => 2, 'confidence' => 1, 'probabilities' => []], 'ctx'), new Question('s', Question::SCORE, 'S?'))->score);
    }

    public function testDistributionAcceptsValuesOrLabels(): void
    {
        $decision = new OpenAiDecision(new OpenAiClient(new JsonEndpoint(new class () implements Transport {
            public function send(array $requests): array
            {
                return [];
            }
        }, ProviderSettings::defaults('openai'), 'k'), 'gpt-6-luna'));
        self::assertSame(['a' => 0.5, 'b' => 0.5], $decision->distribution(new Payload(['probabilities' => [['value' => 'a', 'probability' => 0.5], ['label' => 'b', 'probability' => 0.5]]], 'ctx')));
    }
}
