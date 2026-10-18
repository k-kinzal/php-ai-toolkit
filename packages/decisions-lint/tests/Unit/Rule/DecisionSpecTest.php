<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Rule\DecisionSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\DecisionSpec
 */
#[CoversClass(DecisionSpec::class)]
final class DecisionSpecTest extends TestCase
{
    public function testExposesTheQuestionAndItsThreshold(): void
    {
        $spec = new DecisionSpec('score', 'Rate {name}.', [], ['low', 'high'], 0.8);
        self::assertSame('score', $spec->type);
        self::assertSame('Rate {name}.', $spec->instructions);
        self::assertSame([], $spec->choices);
        self::assertSame(['low', 'high'], $spec->levels);
        self::assertSame(0.8, $spec->threshold);
        self::assertSame(0.5, (new DecisionSpec('predicate', 'Q?'))->threshold);
    }
}
