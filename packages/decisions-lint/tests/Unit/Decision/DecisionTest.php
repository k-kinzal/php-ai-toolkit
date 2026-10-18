<?php

declare(strict_types=1);

namespace Tests\Unit\Decision;

use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Decision;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
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
        $decision = new class () implements Decision {
            public function decide(array $inquiries): array
            {
                $verdicts = [];
                foreach ($inquiries as $inquiry) {
                    $answers = [];
                    foreach ($inquiry->questions as $question) {
                        $answers[$question->name] = Answer::predicate(0.5);
                    }
                    $verdicts[] = new Verdict('fake', $answers);
                }

                return $verdicts;
            }
        };
        $verdicts = $decision->decide([new Inquiry(['a' => '1'], [new Question('q', Question::PREDICATE, 'Q?')]), new Inquiry(['b' => '2'], [])]);
        self::assertCount(2, $verdicts);
        self::assertSame(0.5, $verdicts[0]->answer('q')->probability);
        self::assertSame([], $verdicts[1]->answers);
    }
}
