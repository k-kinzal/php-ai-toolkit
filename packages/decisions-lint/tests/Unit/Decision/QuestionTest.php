<?php

declare(strict_types=1);

namespace Tests\Unit\Decision;

use DecisionsLint\Decision\Question;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Decision\Question
 */
#[CoversClass(Question::class)]
final class QuestionTest extends TestCase
{
    public function testExposesTheTypedQuestion(): void
    {
        $question = new Question('ddd.leak', Question::CHOICE, 'Classify the method.', ['a' => 'A', 'b' => 'B'], []);
        self::assertSame('ddd.leak', $question->name);
        self::assertSame('choice', $question->type);
        self::assertSame('Classify the method.', $question->instructions);
        self::assertSame(['a' => 'A', 'b' => 'B'], $question->choices);
        self::assertSame([], $question->levels);
        self::assertSame(['predicate', 'choice', 'score'], Question::TYPES);
    }
}
