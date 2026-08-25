<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffLine;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;

/**
 * @covers \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffLine
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
 */
#[CoversClass(LineDiffer::class)]
#[UsesClass(DiffLine::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(LcsMatcher::class)]
final class LineDifferTest extends TestCase
{
    public function testLinesSplitsATextAndNormalizesWindowsLineEndings(): void
    {
        self::assertSame(['one', 'two', ''], (new LineDiffer())->lines("one\r\ntwo\n"));
        self::assertSame([''], (new LineDiffer())->lines(''));
    }

    public function testMergeNumbersKeptLinesAfterTheHeadRevision(): void
    {
        $differ = new LineDiffer();

        $lines = $differ->merge(['a', 'b'], ['a', 'b'], ['A', 'B'], ['A', 'B']);

        self::assertCount(2, $lines);
        self::assertSame(DiffStatus::SAME, $lines[0]->status);
        self::assertSame('A', $lines[0]->text);
        self::assertSame(1, $lines[0]->baseNumber);
        self::assertSame(1, $lines[0]->headNumber);
        self::assertSame(2, $lines[1]->headNumber);
    }

    public function testMergeShowsTheDisplayedFormOfEachRevision(): void
    {
        $differ = new LineDiffer();

        $lines = $differ->merge(['a', 'gone'], ['a', 'new'], ['A', 'GONE'], ['A', 'NEW']);

        self::assertCount(3, $lines);
        self::assertSame(DiffStatus::REMOVED, $lines[1]->status);
        self::assertSame('GONE', $lines[1]->text);
        self::assertSame(2, $lines[1]->baseNumber);
        self::assertNull($lines[1]->headNumber);
        self::assertSame(DiffStatus::ADDED, $lines[2]->status);
        self::assertSame('NEW', $lines[2]->text);
        self::assertNull($lines[2]->baseNumber);
        self::assertSame(2, $lines[2]->headNumber);
    }

    public function testMergeTreatsAMissingRevisionAsAWholeAdditionOrRemoval(): void
    {
        $differ = new LineDiffer();

        $added = $differ->merge([], ['a'], [], ['A']);
        $removed = $differ->merge(['a'], [], ['A'], []);

        self::assertSame(DiffStatus::ADDED, $added[0]->status);
        self::assertSame(DiffStatus::REMOVED, $removed[0]->status);
        self::assertSame('A', $removed[0]->text);
    }

    public function testMergeFallsBackToEmptyTextWhenTheDisplayedLinesAreShorter(): void
    {
        $lines = (new LineDiffer())->merge(['a'], ['b'], [], []);

        self::assertSame('', $lines[0]->text);
        self::assertSame('', $lines[1]->text);
    }
}
