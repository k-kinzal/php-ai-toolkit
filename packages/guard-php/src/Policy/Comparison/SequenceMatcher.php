<?php

declare(strict_types=1);

namespace Guard\Policy\Comparison;

use function array_reverse;
use function count;
use function usort;

/**
 * Matches items against an ordered list of slots, each required once, optional, or repeatable.
 *
 * The matcher finds the alignment with the fewest findings, where each skipped item and each unfilled required
 * slot is one finding. A repeatable wildcard slot therefore never swallows an item a later slot needs, and a
 * renamed section is reported once as missing instead of failing every heading after it.
 */
final class SequenceMatcher
{
    /**
     * Returns the unexpected items and the required slots left unfilled.
     *
     * @param list<array{0: bool, 1: bool}> $slots the optional and repeat flags of each slot
     * @param list<list<bool>> $accepts whether each item fits each slot, indexed by item then slot
     */
    public function match(array $slots, array $accepts): SequenceResult
    {
        $end = count($slots);
        $layers = [$this->settle($slots, [0 => [0 => [0, null]]], 0)];
        foreach ($accepts as $item => $fits) {
            $next = [];
            foreach ($this->advancedFirst($layers[$item]) as [$slot, $filled, $cost]) {
                if ($slot < $end && ($fits[$slot] ?? false) && ($filled === 0 || $slots[$slot][1])) {
                    $next = $this->relax($next, $slot, 1, $cost, [$item, $slot, $filled, 'take']);
                }
                $next = $this->relax($next, $slot, $filled, $cost + 1, [$item, $slot, $filled, 'skip']);
            }
            $layers[] = $this->settle($slots, $next, $item + 1);
        }
        $unexpected = [];
        $missing = [];
        $previous = $layers[count($accepts)][$end][0][1] ?? null;
        while ($previous !== null) {
            [$layer, $slot, $filled, $action] = $previous;
            if ($action === 'skip') {
                $unexpected[] = [$layer, $this->expected($slots, $slot, $filled)];
            } elseif ($action === 'miss') {
                $missing[] = $slot;
            }
            $previous = $layers[$layer][$slot][$filled][1] ?? null;
        }

        return new SequenceResult(array_reverse($unexpected), array_reverse($missing));
    }

    /**
     * Lists the states of one layer from the furthest slot back, so that on a tie the items already matched stay
     * matched and the later duplicate is the one reported.
     *
     * @param array<int, array<int, array{0: int, 1: ?array{0: int, 1: int, 2: int, 3: string}}>> $states
     * @return list<array{0: int, 1: int, 2: int}> slot, filled, and cost of each state
     */
    public function advancedFirst(array $states): array
    {
        $ordered = [];
        foreach ($states as $slot => $byFilled) {
            foreach ($byFilled as $filled => [$cost]) {
                $ordered[] = [$slot, $filled, $cost];
            }
        }
        usort($ordered, static fn (array $left, array $right): int => [$right[0], $right[1]] <=> [$left[0], $left[1]]);

        return $ordered;
    }

    /**
     * Moves every state of one layer past its slot, counting a finding for each required slot left unfilled.
     *
     * @param list<array{0: bool, 1: bool}> $slots
     * @param array<int, array<int, array{0: int, 1: ?array{0: int, 1: int, 2: int, 3: string}}>> $states
     * @return array<int, array<int, array{0: int, 1: ?array{0: int, 1: int, 2: int, 3: string}}>>
     */
    public function settle(array $slots, array $states, int $layer): array
    {
        foreach ($slots as $slot => [$optional]) {
            foreach ([0, 1] as $filled) {
                if (!isset($states[$slot][$filled])) {
                    continue;
                }
                $miss = $filled === 0 && !$optional;
                $states = $this->relax($states, $slot + 1, 0, $states[$slot][$filled][0] + ($miss ? 1 : 0), [$layer, $slot, $filled, $miss ? 'miss' : 'leave'], true);
            }
        }

        return $states;
    }

    /**
     * Records a cheaper way to reach a state. A tie keeps the earlier way unless $replaceTie is set.
     *
     * Settling replaces ties so that an unexpected item is reported at the earliest position it could occupy,
     * where the most headings are allowed, rather than after optional slots were already passed.
     *
     * @param array<int, array<int, array{0: int, 1: ?array{0: int, 1: int, 2: int, 3: string}}>> $states
     * @param array{0: int, 1: int, 2: int, 3: string} $previous
     * @return array<int, array<int, array{0: int, 1: ?array{0: int, 1: int, 2: int, 3: string}}>>
     */
    public function relax(array $states, int $slot, int $filled, int $cost, array $previous, bool $replaceTie = false): array
    {
        if (!isset($states[$slot][$filled]) || $cost < $states[$slot][$filled][0] || ($replaceTie && $cost === $states[$slot][$filled][0])) {
            $states[$slot][$filled] = [$cost, $previous];
        }

        return $states;
    }

    /**
     * Returns the slots that could take the next item, up to the first required one.
     *
     * @param list<array{0: bool, 1: bool}> $slots
     * @return list<int>
     */
    public function expected(array $slots, int $slot, int $filled): array
    {
        $expected = [];
        for ($index = $slot; $index < count($slots); $index++) {
            [$optional, $repeat] = $slots[$index];
            if ($filled === 0 || $repeat) {
                $expected[] = $index;
            }
            if ($filled === 0 && !$optional) {
                break;
            }
            $filled = 0;
        }

        return $expected;
    }
}
