<?php

declare(strict_types=1);

namespace Tests\Unit\Decision;

use DecisionsLint\Decision\Answer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Decision\Answer
 */
#[CoversClass(Answer::class)]
final class AnswerTest extends TestCase
{
    public function testPredicateDerivesConfidenceFromTheProbability(): void
    {
        $answer = Answer::predicate(0.2);
        self::assertSame('predicate', $answer->type);
        self::assertSame(0.2, $answer->probability);
        self::assertSame(0.8, $answer->confidence);
        self::assertSame(['true' => 0.2, 'false' => 0.8], $answer->probabilities);
    }

    public function testChoiceKeepsTheOptionAndTheDistribution(): void
    {
        $answer = Answer::choice('domain_rule', 0.7, ['domain_rule' => 0.7, 'orchestration' => 0.3]);
        self::assertSame('choice', $answer->type);
        self::assertSame('domain_rule', $answer->choice);
        self::assertSame(0.7, $answer->confidence);
        self::assertSame(['domain_rule' => 0.7, 'orchestration' => 0.3], $answer->probabilities);
    }

    public function testScoreKeepsTheWeightedLevel(): void
    {
        $answer = Answer::score(1.4, 0.6, ['0' => 0.1, '1' => 0.4, '2' => 0.5]);
        self::assertSame('score', $answer->type);
        self::assertSame(1.4, $answer->score);
        self::assertSame(0.6, $answer->confidence);
    }

    public function testRefusalCarriesNothingButItsType(): void
    {
        $answer = Answer::refusal();
        self::assertSame(Answer::REFUSAL, $answer->type);
        self::assertSame(0.0, $answer->probability);
        self::assertSame('', $answer->choice);
        self::assertSame([], $answer->probabilities);
    }
}
