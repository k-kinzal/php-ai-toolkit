<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parallel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkScheduler;

/**
 * @covers \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 */
#[CoversClass(WorkScheduler::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(WorkerCount::class)]
final class WorkSchedulerTest extends TestCase
{
    public function testScheduleKeepsEveryItemInOriginalOrderAcrossConsecutiveJobs(): void
    {
        $jobs = (new WorkScheduler())->schedule([1, 2, 3, 4, 5, 6], static fn (int $item): int => 1, 3);

        self::assertSame([[1, 2], [3, 4], [5, 6]], $jobs);
    }

    public function testScheduleCutsJobsWhereTheWeightOfTheItemsCrossesAnEqualShare(): void
    {
        $jobs = (new WorkScheduler())->schedule([10, 1, 1, 1, 10, 1], static fn (int $item): int => $item, 3);

        self::assertSame([[10], [1, 1, 1, 10], [1]], $jobs);
    }

    public function testScheduleReturnsOneJobWhenThereIsNothingToSplit(): void
    {
        $weight = static fn (int $item): int => 1;

        self::assertSame([[1, 2, 3]], (new WorkScheduler())->schedule([1, 2, 3], $weight, 1));
        self::assertSame([[1, 2, 3]], (new WorkScheduler())->schedule([1, 2, 3], $weight, 0));
        self::assertSame([[7]], (new WorkScheduler())->schedule([7], $weight, 4));
        self::assertSame([], (new WorkScheduler())->schedule([], $weight, 4));
    }

    public function testScheduleNeverProducesMoreJobsThanItemsOrLeavesOneEmpty(): void
    {
        $jobs = (new WorkScheduler())->schedule([1, 2, 3], static fn (int $item): int => 100, 8);

        self::assertCount(3, $jobs);
        self::assertSame([[1], [2], [3]], $jobs);
    }

    public function testWeighNeverReportsAnItemLighterThanOne(): void
    {
        self::assertSame([1, 1, 5], (new WorkScheduler())->weigh([1, 2, 3], static fn (int $item): int => $item === 3 ? 5 : 0));
        self::assertSame([], (new WorkScheduler())->weigh([], static fn (int $item): int => 1));
    }

    public function testAffordableWorkersStaysWithinWhatTheItemsAfford(): void
    {
        $scheduler = new WorkScheduler();

        self::assertSame(4, $scheduler->affordableWorkers(4, 1000));
        self::assertSame(2, $scheduler->affordableWorkers(8, 16));
        self::assertSame(1, $scheduler->affordableWorkers(8, 7));
        self::assertSame(1, $scheduler->affordableWorkers(0, 1000));
    }

    public function testPlanCutsAPhaseIntoTheJobsItsWorkersWillRun(): void
    {
        $scheduler = new WorkScheduler();
        $weight = static fn (int $item): int => 1;

        self::assertSame([range(1, 16)], $scheduler->plan(range(1, 16), $weight, 1));
        self::assertSame([range(1, 8), range(9, 16)], $scheduler->plan(range(1, 16), $weight, 2));
        self::assertSame([[1, 2, 3]], $scheduler->plan([1, 2, 3], $weight, 4));
        self::assertSame([], $scheduler->plan([], $weight, null));
    }
}
