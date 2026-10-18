<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Fake;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Question;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\ChatRequestReader;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Fake\ChatRequestReader
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Payload
 */
#[CoversClass(ChatRequestReader::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Question::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(Payload::class)]
final class ChatRequestReaderTest extends TestCase
{
    public function testSubjectIsTheUserMessage(): void
    {
        $reader = new ChatRequestReader();
        self::assertSame(['input' => 'source'], $reader->subject(new Payload(['messages' => [['role' => 'system', 'content' => 'rules'], ['role' => 'user', 'content' => 'source']]], 'ctx')));
        self::assertSame(['input' => ''], $reader->subject(new Payload(['messages' => []], 'ctx')));
    }

    public function testQuestionsAreRebuiltFromTheAnswerSchema(): void
    {
        $schema = new Payload(['type' => 'object', 'properties' => [
            'p' => ['properties' => ['probability' => ['type' => 'number']]],
            'c' => ['properties' => ['choice' => ['type' => 'string', 'enum' => ['a', 'b']], 'confidence' => ['type' => 'number']]],
            's' => ['properties' => ['score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2], 'confidence' => ['type' => 'number']]],
        ]], 'ctx');
        $questions = (new ChatRequestReader())->questions($schema);
        self::assertSame(['p', 'c', 's'], array_map(static fn (Question $question): string => $question->name, $questions));
        self::assertSame(Question::PREDICATE, $questions[0]->type);
        self::assertSame(['a' => '', 'b' => ''], $questions[1]->choices);
        self::assertCount(3, $questions[2]->levels);
    }

    public function testAnswersWritesOneObjectPerQuestion(): void
    {
        $questions = [new Question('p', Question::PREDICATE, 'P?'), new Question('c', Question::CHOICE, 'C?', ['a' => '']), new Question('s', Question::SCORE, 'S?', [], ['x', 'y'])];
        self::assertSame(['p' => ['probability' => 1.0], 'c' => ['choice' => 'a', 'confidence' => 1.0], 's' => ['score' => 1, 'confidence' => 1.0]], (new ChatRequestReader())->answers($questions, ['input' => ''], new AnswerScript()));
    }

    public function testEncodeRoundsScoresToTheNearestLevel(): void
    {
        $reader = new ChatRequestReader();
        self::assertSame(['probability' => 0.4], $reader->encode(Answer::predicate(0.4), new Question('p', Question::PREDICATE, 'P?')));
        self::assertSame(['choice' => 'b', 'confidence' => 0.6], $reader->encode(Answer::choice('b', 0.6, []), new Question('c', Question::CHOICE, 'C?')));
        self::assertSame(['score' => 2, 'confidence' => 0.7], $reader->encode(Answer::score(1.6, 0.7, []), new Question('s', Question::SCORE, 'S?')));
    }
}
