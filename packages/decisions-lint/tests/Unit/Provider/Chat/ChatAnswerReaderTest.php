<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Chat;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Chat\ChatAnswerReader;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Chat\ChatAnswerReader
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\Payload
 */
#[CoversClass(ChatAnswerReader::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(LintException::class)]
#[UsesClass(Payload::class)]
final class ChatAnswerReaderTest extends TestCase
{
    public function testVerdictReadsEveryAnswerAndRefusesTheMissingOnes(): void
    {
        $inquiry = new Inquiry([], [
            new Question('p', Question::PREDICATE, 'P?'),
            new Question('c', Question::CHOICE, 'C?', ['a' => 'A', 'b' => 'B']),
            new Question('s', Question::SCORE, 'S?', [], ['x', 'y', 'z']),
            new Question('missing', Question::PREDICATE, 'M?'),
        ]);
        $verdict = (new ChatAnswerReader())->verdict("```json\n{\"p\":{\"probability\":0.2},\"c\":{\"choice\":\"b\",\"confidence\":0.9},\"s\":{\"score\":2,\"confidence\":0.7}}\n```", 'llama3.1', $inquiry, 'Ollama');
        self::assertSame('llama3.1', $verdict->model);
        self::assertSame(0.2, $verdict->answer('p')->probability);
        self::assertSame('b', $verdict->answer('c')->choice);
        self::assertSame(['b' => 0.9], $verdict->answer('c')->probabilities);
        self::assertSame(2.0, $verdict->answer('s')->score);
        self::assertSame(Answer::REFUSAL, $verdict->answer('missing')->type);
    }

    public function testDecodeRejectsTextThatIsNotJson(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Ollama answered with text that is not JSON: Sure! Here is my answer. Use a model that supports structured JSON output');
        (new ChatAnswerReader())->decode('Sure!  Here is my answer', 'Ollama');
    }

    public function testDecodeRejectsAJsonScalar(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('The chat model answered with JSON that is not an object: 0.5.');
        (new ChatAnswerReader())->decode('0.5', 'The chat model');
    }

    public function testAnswerClampsNumbersAndRefusesAnUnknownOption(): void
    {
        $reader = new ChatAnswerReader();
        self::assertSame(1.0, $reader->answer(new Payload(['probability' => 1.7], 'ctx'), new Question('p', Question::PREDICATE, 'P?'))->probability);
        self::assertSame(Answer::REFUSAL, $reader->answer(new Payload(['choice' => 'z', 'confidence' => 1], 'ctx'), new Question('c', Question::CHOICE, 'C?', ['a' => 'A']))->type);
        $score = $reader->answer(new Payload(['score' => 9, 'confidence' => -1], 'ctx'), new Question('s', Question::SCORE, 'S?', [], ['x', 'y']));
        self::assertSame(1.0, $score->score);
        self::assertSame(0.0, $score->confidence);
        self::assertSame(['1' => 0.0], $score->probabilities);
    }

    public function testUnitClampsIntoTheInterval(): void
    {
        $reader = new ChatAnswerReader();
        self::assertSame(0.0, $reader->unit(-0.2));
        self::assertSame(0.4, $reader->unit(0.4));
        self::assertSame(1.0, $reader->unit(3.0));
    }

    public function testExcerptFlattensAndShortensText(): void
    {
        $reader = new ChatAnswerReader();
        self::assertSame('a b', $reader->excerpt("a\n\n   b"));
        self::assertSame(str_repeat('x', 120) . '…', $reader->excerpt(str_repeat('x', 200)));
    }
}
