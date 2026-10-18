<?php

declare(strict_types=1);

namespace Tests\Unit\Decision;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Provider\Fake\FakeDecision;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class DecisionTest extends TestCase
{
    public function testDecideReturnsOneVerdictPerInquiryInOrder(): void
    {
        $decision = new FakeDecision();
        $verdicts = $decision->decide([new Inquiry(['a' => '1'], [new Question('q', Question::PREDICATE, 'Q?')]), new Inquiry(['b' => '2'], [])]);
        self::assertCount(2, $verdicts);
        self::assertSame(1.0, $verdicts[0]->answer('q')->probability);
        self::assertSame([], $verdicts[1]->answers);
    }
}
