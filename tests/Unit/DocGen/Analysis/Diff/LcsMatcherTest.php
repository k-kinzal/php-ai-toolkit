<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher
 */
#[CoversClass(LcsMatcher::class)]
final class LcsMatcherTest extends TestCase
{
    public function testMatchPairsTwoIdenticalSequencesPositionByPosition(): void
    {
        self::assertSame(
            [['base' => 0, 'head' => 0], ['base' => 1, 'head' => 1]],
            (new LcsMatcher())->match(['a', 'b'], ['a', 'b']),
        );
    }

    public function testMatchReportsAnInsertionBetweenTwoKeptElements(): void
    {
        self::assertSame(
            [['base' => 0, 'head' => 0], ['base' => null, 'head' => 1], ['base' => 1, 'head' => 2]],
            (new LcsMatcher())->match(['a', 'c'], ['a', 'b', 'c']),
        );
    }

    public function testMatchReportsARemovalBetweenTwoKeptElements(): void
    {
        self::assertSame(
            [['base' => 0, 'head' => 0], ['base' => 1, 'head' => null], ['base' => 2, 'head' => 1]],
            (new LcsMatcher())->match(['a', 'b', 'c'], ['a', 'c']),
        );
    }

    public function testMatchReportsAReplacementAsARemovalFollowedByAnAddition(): void
    {
        self::assertSame(
            [['base' => 0, 'head' => 0], ['base' => 1, 'head' => null], ['base' => null, 'head' => 1], ['base' => 2, 'head' => 2]],
            (new LcsMatcher())->match(['a', 'b', 'd'], ['a', 'c', 'd']),
        );
    }

    public function testMatchHandlesAnEmptySequenceOnEitherSide(): void
    {
        self::assertSame([['base' => null, 'head' => 0]], (new LcsMatcher())->match([], ['a']));
        self::assertSame([['base' => 0, 'head' => null]], (new LcsMatcher())->match(['a'], []));
        self::assertSame([], (new LcsMatcher())->match([], []));
    }

    public function testMiddleTreatsTwoSequencesWithoutAnythingInCommonAsAReplacement(): void
    {
        self::assertSame(
            [['base' => 0, 'head' => null], ['base' => 1, 'head' => null], ['base' => null, 'head' => 0]],
            (new LcsMatcher())->middle(['a', 'b'], ['c']),
        );
    }

    public function testTableCountsTheCommonSubsequenceFromEveryPosition(): void
    {
        $table = (new LcsMatcher())->table(['a', 'b', 'c'], ['a', 'c']);

        self::assertSame(2, $table[0][0]);
        self::assertSame(1, $table[1][1]);
        self::assertSame(0, $table[3][2]);
    }

    public function testWalkFollowsTheTableFromTheStartOfBothSequences(): void
    {
        $matcher = new LcsMatcher();

        self::assertSame(
            [['base' => 0, 'head' => 0], ['base' => 1, 'head' => null], ['base' => 2, 'head' => 1]],
            $matcher->walk(['a', 'b', 'c'], ['a', 'c'], $matcher->table(['a', 'b', 'c'], ['a', 'c'])),
        );
    }

    public function testTailAppendsWhatNeitherSequenceMatched(): void
    {
        self::assertSame(
            [['base' => 1, 'head' => null], ['base' => null, 'head' => 2]],
            (new LcsMatcher())->tail([], 2, 3, 1, 2),
        );
    }

    public function testReplacementRemovesEverythingBeforeAddingEverything(): void
    {
        self::assertSame(
            [['base' => 0, 'head' => null], ['base' => null, 'head' => 0], ['base' => null, 'head' => 1]],
            (new LcsMatcher())->replacement(1, 2),
        );
    }
}
