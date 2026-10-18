<?php

declare(strict_types=1);

namespace Tests\Unit\Decision;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 */
#[CoversClass(Inquiry::class)]
#[UsesClass(Question::class)]
final class InquiryTest extends TestCase
{
    public function testQuestionFindsAQuestionByName(): void
    {
        $first = new Question('a', Question::PREDICATE, 'A?');
        $second = new Question('b', Question::PREDICATE, 'B?');
        $inquiry = new Inquiry(['file' => 'src/A.php'], [$first, $second]);
        self::assertSame(['file' => 'src/A.php'], $inquiry->subject);
        self::assertSame([$first, $second], $inquiry->questions);
        self::assertSame($second, $inquiry->question('b'));
        self::assertNull($inquiry->question('missing'));
    }
}
