<?php

declare(strict_types=1);

namespace Tests\Unit\Decision;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Verdict;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Decision\Answer
 */
#[CoversClass(Verdict::class)]
#[UsesClass(Answer::class)]
final class VerdictTest extends TestCase
{
    public function testAnswerReturnsTheAnswerOrARefusal(): void
    {
        $answer = Answer::predicate(0.9);
        $verdict = new Verdict('jev-1.13.0', ['rule.a' => $answer]);
        self::assertSame('jev-1.13.0', $verdict->model);
        self::assertSame(['rule.a' => $answer], $verdict->answers);
        self::assertSame($answer, $verdict->answer('rule.a'));
        self::assertSame(Answer::REFUSAL, $verdict->answer('rule.b')->type);
    }
}
