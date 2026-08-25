<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Parallel;

use function count;
use function max;
use function min;

/**
 * Splits the work of one generation phase into balanced worker jobs.
 *
 * Work items are not equally expensive: one source file can be twenty
 * times the size of the next, and a job that collected the heaviest of
 * them would still be running long after every other worker went idle.
 * Jobs are therefore cut where the accumulated weight of the items crosses
 * an equal share of the total, which balances them without reordering
 * anything: every job is a run of consecutive items, so putting the
 * results of the jobs back together in job order puts the items back in
 * exactly the order a single process would have worked through them.
 *
 * That ordering is the whole point. What the analysis finds and what the
 * renderer writes both depend on the order the items were worked in, so a
 * scheme that mixed items between jobs would have to carry each item's
 * original position through the workers and sort them again on the way
 * back. Consecutive jobs make that bookkeeping unnecessary.
 */
final class WorkScheduler
{
    /**
     * The fewest items a job should hold before another worker is started.
     *
     * A worker costs a fork, a serialized result, and a process to wait
     * for, which is not worth paying for a handful of items.
     */
    public const MINIMUM_ITEMS_PER_JOB = 8;

    /** @readonly */
    private WorkerCount $workers;

    /**
     * Creates a scheduler from the worker count of this machine.
     */
    public function __construct(?WorkerCount $workers = null)
    {
        $this->workers = $workers ?? new WorkerCount();
    }

    /**
     * Cuts the items of one phase into the jobs its workers will run.
     *
     * @template TItem
     *
     * @param list<TItem> $items
     * @param callable(TItem): int $weight the relative cost of one item
     * @param ?int $workers the count a run asked for, or null for the default
     *
     * @return list<list<TItem>>
     */
    public function plan(array $items, callable $weight, ?int $workers): array
    {
        return $this->schedule($items, $weight, $this->affordableWorkers($this->workers->resolve($workers), count($items)));
    }

    /**
     * Cuts the items into one job per worker, balanced by weight.
     *
     * @template TItem
     *
     * @param list<TItem> $items
     * @param callable(TItem): int $weight the relative cost of one item
     * @param int $jobs how many jobs to cut into, at least one
     *
     * @return list<list<TItem>> consecutive runs, together the whole list
     */
    public function schedule(array $items, callable $weight, int $jobs): array
    {
        $count = min(max($jobs, 1), count($items));
        if ($count < 2) {
            return $items === [] ? [] : [$items];
        }

        $weights = $this->weigh($items, $weight);
        $total = 0;
        foreach ($weights as $itemWeight) {
            $total += $itemWeight;
        }

        $scheduled = [];
        $job = [];
        $carried = 0;
        $left = count($items);
        foreach ($items as $position => $item) {
            $job[] = $item;
            $carried += $weights[$position];
            $left--;
            $open = $count - count($scheduled);
            if ($open > 1 && $left >= $open - 1 && $carried * $count >= $total * (count($scheduled) + 1)) {
                $scheduled[] = $job;
                $job = [];
            }
        }

        if ($job !== []) {
            $scheduled[] = $job;
        }

        return $scheduled;
    }

    /**
     * Weighs every item, never lighter than one.
     *
     * An item of no weight at all would let a whole run of items fall into
     * one job while the scheduler believed it had shared them out, so the
     * count of the items is the floor under whatever the caller measures.
     *
     * @template TItem
     *
     * @param list<TItem> $items
     * @param callable(TItem): int $weight
     *
     * @return list<int>
     */
    public function weigh(array $items, callable $weight): array
    {
        $weights = [];
        foreach ($items as $item) {
            $weights[] = max($weight($item), 1);
        }

        return $weights;
    }

    /**
     * Returns how many workers one phase should be split across.
     *
     * @param int $requested the worker count asked for, such as by --jobs
     * @param int $items how many items the phase has to work through
     */
    public function affordableWorkers(int $requested, int $items): int
    {
        $affordable = (int) ($items / self::MINIMUM_ITEMS_PER_JOB);

        return max(min(max($requested, 1), $affordable), 1);
    }
}
