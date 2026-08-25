<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus
 */
#[CoversClass(MemberMerger::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiffStatus::class)]
final class MemberMergerTest extends TestCase
{
    public function testMergeKeepsTheHeadOrderAndPutsRemovalsBackWhereTheyWere(): void
    {
        $merged = (new MemberMerger())->merge(
            [new ConstantDoc('A', 'public', '1', null, 1), new ConstantDoc('B', 'public', '2', null, 2), new ConstantDoc('C', 'public', '3', null, 3)],
            [new ConstantDoc('A', 'public', '1', null, 1), new ConstantDoc('C', 'public', '3', null, 3)],
            static fn (ConstantDoc $constant): string => $constant->name,
            static fn (ConstantDoc $constant): string => $constant->name . '=' . $constant->valueText,
        );

        self::assertCount(3, $merged);
        self::assertSame('A', $merged[0]['item']->name);
        self::assertSame(DiffStatus::SAME, $merged[0]['status']);
        self::assertSame('B', $merged[1]['item']->name);
        self::assertSame(DiffStatus::REMOVED, $merged[1]['status']);
        self::assertSame('C', $merged[2]['item']->name);
        self::assertSame(DiffStatus::SAME, $merged[2]['status']);
    }

    public function testMergeReportsNewAndChangedMembers(): void
    {
        $merged = (new MemberMerger())->merge(
            [new ConstantDoc('A', 'public', '1', null, 1)],
            [new ConstantDoc('A', 'public', '2', null, 1), new ConstantDoc('B', 'public', '3', null, 2)],
            static fn (ConstantDoc $constant): string => $constant->name,
            static fn (ConstantDoc $constant): string => $constant->name . '=' . $constant->valueText,
        );

        self::assertSame(DiffStatus::MODIFIED, $merged[0]['status']);
        self::assertSame(DiffStatus::ADDED, $merged[1]['status']);
    }

    public function testMergeReadsAMovedMemberAsUnchanged(): void
    {
        $merged = (new MemberMerger())->merge(
            [new ConstantDoc('A', 'public', '1', null, 1), new ConstantDoc('B', 'public', '2', null, 2)],
            [new ConstantDoc('B', 'public', '2', null, 1), new ConstantDoc('A', 'public', '1', null, 2)],
            static fn (ConstantDoc $constant): string => $constant->name,
            static fn (ConstantDoc $constant): string => $constant->name . '=' . $constant->valueText,
        );

        self::assertCount(2, $merged);
        self::assertSame(DiffStatus::SAME, $merged[0]['status']);
        self::assertSame(DiffStatus::SAME, $merged[1]['status']);
    }

    public function testMergePutsRemovalsFirstWhenNothingPrecededThem(): void
    {
        $merged = (new MemberMerger())->merge(
            [new ConstantDoc('A', 'public', '1', null, 1), new ConstantDoc('B', 'public', '2', null, 2)],
            [new ConstantDoc('B', 'public', '2', null, 2)],
            static fn (ConstantDoc $constant): string => $constant->name,
            static fn (ConstantDoc $constant): string => $constant->name,
        );

        self::assertSame('A', $merged[0]['item']->name);
        self::assertSame(DiffStatus::REMOVED, $merged[0]['status']);
        self::assertSame('B', $merged[1]['item']->name);
    }

    public function testStatusOfComparesOneMemberAgainstItsCounterpart(): void
    {
        $merger = new MemberMerger();
        $fingerprint = static fn (ConstantDoc $constant): string => $constant->name . '=' . $constant->valueText;
        $head = new ConstantDoc('A', 'public', '1', null, 1);

        self::assertSame(DiffStatus::ADDED, $merger->statusOf(null, $head, $fingerprint));
        self::assertSame(DiffStatus::SAME, $merger->statusOf(new ConstantDoc('A', 'public', '1', null, 9), $head, $fingerprint));
        self::assertSame(DiffStatus::MODIFIED, $merger->statusOf(new ConstantDoc('A', 'public', '2', null, 1), $head, $fingerprint));
    }

    public function testPositionForPlacesARemovalBehindItsNearestSurvivingNeighbour(): void
    {
        $merger = new MemberMerger();
        $name = static fn (ConstantDoc $constant): string => $constant->name;
        $base = [new ConstantDoc('A', 'public', '1', null, 1), new ConstantDoc('B', 'public', '2', null, 2)];
        $merged = [['item' => $base[0], 'status' => DiffStatus::SAME]];

        self::assertSame(1, $merger->positionFor($base, 1, $merged, $name));
        self::assertSame(0, $merger->positionFor($base, 0, $merged, $name));
    }

    public function testIndexOfFindsTheEntryOfOneName(): void
    {
        $merger = new MemberMerger();
        $name = static fn (ConstantDoc $constant): string => $constant->name;
        $merged = [
            ['item' => new ConstantDoc('A', 'public', '1', null, 1), 'status' => DiffStatus::SAME],
            ['item' => new ConstantDoc('B', 'public', '2', null, 2), 'status' => DiffStatus::ADDED],
        ];

        self::assertSame(1, $merger->indexOf($merged, 'B', $name));
        self::assertNull($merger->indexOf($merged, 'C', $name));
    }

    public function testInsertPutsOneEntryAtTheGivenPosition(): void
    {
        $merger = new MemberMerger();
        $first = ['item' => new ConstantDoc('A', 'public', '1', null, 1), 'status' => DiffStatus::SAME];
        $second = ['item' => new ConstantDoc('B', 'public', '2', null, 2), 'status' => DiffStatus::REMOVED];

        self::assertSame([$second, $first], $merger->insert([$first], $second, 0));
        self::assertSame([$first, $second], $merger->insert([$first], $second, 1));
    }

    public function testFindLooksOneMemberUpByName(): void
    {
        $merger = new MemberMerger();
        $items = [new ConstantDoc('A', 'public', '1', null, 1), new ConstantDoc('B', 'public', '2', null, 2)];

        self::assertSame($items[1], $merger->find($items, 'B', static fn (ConstantDoc $constant): string => $constant->name));
        self::assertNull($merger->find($items, 'C', static fn (ConstantDoc $constant): string => $constant->name));
    }
}
