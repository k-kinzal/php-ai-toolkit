<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use Guard\Policy\Comparison\SequenceMatcher;
use Guard\Policy\Comparison\SequenceResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\SequenceMatcher
 * @uses \Guard\Policy\Comparison\SequenceResult
 */
#[CoversClass(SequenceMatcher::class)]
#[UsesClass(SequenceResult::class)]
final class SequenceMatcherTest extends TestCase
{
    public function testMatchLetsARepeatedWildcardLeaveTheLastItemToALaterSlot(): void
    {
        $result = (new SequenceMatcher())->match(
            [[false, false], [true, true], [false, false]],
            [[true, false, false], [false, true, false], [false, true, false], [false, false, true]],
        );

        self::assertSame([], $result->unexpected);
        self::assertSame([], $result->missing);
    }

    public function testMatchReportsAndSkipsAnItemNoReachableSlotAccepts(): void
    {
        $result = (new SequenceMatcher())->match(
            [[false, false], [true, false], [false, false]],
            [[true, false, false], [false, false, false], [false, false, true]],
        );

        self::assertSame([[1, [1, 2]]], $result->unexpected);
        self::assertSame([], $result->missing);
    }

    public function testMatchReportsARepeatedSingleSlotItem(): void
    {
        $result = (new SequenceMatcher())->match([[false, false]], [[true], [true]]);

        self::assertSame([[1, []]], $result->unexpected);
    }

    public function testMatchReportsTheFewestMissingRequiredSlots(): void
    {
        $result = (new SequenceMatcher())->match(
            [[false, false], [false, false], [true, false], [false, false]],
            [[true, false, false, false]],
        );

        self::assertSame([], $result->unexpected);
        self::assertSame([1, 3], $result->missing);
    }

    public function testMatchPrefersOneMissingSlotOverSeveralUnexpectedItems(): void
    {
        $result = (new SequenceMatcher())->match(
            [[false, false], [false, false], [true, true], [false, false]],
            [[true, false, false, false], [false, false, true, false], [false, false, false, true]],
        );

        self::assertSame([], $result->unexpected);
        self::assertSame([1], $result->missing);
    }

    public function testSettleChargesOnlyForLeavingAnUnfilledRequiredSlot(): void
    {
        $states = (new SequenceMatcher())->settle([[true, false], [false, false]], [0 => [0 => [0, null]]], 0);

        self::assertSame([0, [0, 0, 0, 'leave']], $states[1][0]);
        self::assertSame([1, [0, 1, 0, 'miss']], $states[2][0]);
    }

    public function testRelaxKeepsTheEarlierStateOnATie(): void
    {
        $states = (new SequenceMatcher())->relax([1 => [0 => [2, [0, 0, 0, 'take']]]], 1, 0, 2, [0, 0, 0, 'skip']);
        $states = (new SequenceMatcher())->relax($states, 1, 1, 0, [0, 1, 0, 'take']);

        self::assertSame([2, [0, 0, 0, 'take']], $states[1][0]);
        self::assertSame([0, [0, 1, 0, 'take']], $states[1][1]);
        self::assertSame([2, [1, 0, 0, 'leave']], (new SequenceMatcher())->relax($states, 1, 0, 2, [1, 0, 0, 'leave'], true)[1][0]);
    }

    public function testAdvancedFirstOrdersStatesByFurthestSlotThenFilled(): void
    {
        $ordered = (new SequenceMatcher())->advancedFirst([0 => [0 => [1, null], 1 => [0, null]], 2 => [0 => [3, null]]]);

        self::assertSame([[2, 0, 3], [0, 1, 0], [0, 0, 1]], $ordered);
    }

    public function testExpectedListsSlotsUpToTheFirstRequiredOne(): void
    {
        $slots = [[false, false], [true, false], [true, true], [false, false], [false, false]];

        self::assertSame([1, 2, 3], (new SequenceMatcher())->expected($slots, 0, 1));
        self::assertSame([2, 3], (new SequenceMatcher())->expected($slots, 2, 1));
        self::assertSame([], (new SequenceMatcher())->expected([[false, false]], 0, 1));
    }
}
