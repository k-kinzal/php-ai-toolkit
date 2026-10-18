<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Rule\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\Violation
 */
#[CoversClass(Violation::class)]
final class ViolationTest extends TestCase
{
    public function testExposesTheViolatingAnswerAndItsTexts(): void
    {
        $violation = new Violation('domain_rule', null, null, 'Leak in {name}.', 'Move it.');
        self::assertSame('domain_rule', $violation->answer);
        self::assertNull($violation->minScore);
        self::assertNull($violation->maxScore);
        self::assertSame('Leak in {name}.', $violation->message);
        self::assertSame('Move it.', $violation->fix);
        $score = new Violation(null, 0.5, 1.5, 'Vague.', 'Rename.');
        self::assertSame(0.5, $score->minScore);
        self::assertSame(1.5, $score->maxScore);
    }
}
