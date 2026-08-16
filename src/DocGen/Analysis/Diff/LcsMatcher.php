<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

use function array_fill;
use function array_slice;
use function count;
use function max;

/**
 * Matches two sequences by their longest common subsequence.
 *
 * The shared head and tail of the sequences are matched directly, which is
 * what a typical edit looks like, and only the differing middle is put
 * through the quadratic table. A middle too large for the table is treated
 * as a full replacement, so a diff of two unrelated files stays fast.
 */
final class LcsMatcher
{
    /**
     * Largest table the differing middle of two sequences may need.
     */
    public const MAX_MATRIX_CELLS = 250000;

    /**
     * Matches two sequences into one merged list of positions.
     *
     * Each entry names the base position, the head position, or both when
     * the element is common to the sequences.
     *
     * @param list<string> $base
     * @param list<string> $head
     *
     * @return list<array{base: ?int, head: ?int}>
     */
    public function match(array $base, array $head): array
    {
        $baseCount = count($base);
        $headCount = count($head);
        $prefix = 0;
        while ($prefix < $baseCount && $prefix < $headCount && $base[$prefix] === $head[$prefix]) {
            $prefix++;
        }

        $suffix = 0;
        while (
            $suffix < $baseCount - $prefix && $suffix < $headCount - $prefix
            && $base[$baseCount - 1 - $suffix] === $head[$headCount - 1 - $suffix]
        ) {
            $suffix++;
        }

        $operations = [];
        for ($index = 0; $index < $prefix; $index++) {
            $operations[] = ['base' => $index, 'head' => $index];
        }

        $middle = $this->middle(
            array_slice($base, $prefix, $baseCount - $prefix - $suffix),
            array_slice($head, $prefix, $headCount - $prefix - $suffix),
        );
        foreach ($middle as $operation) {
            $operations[] = [
                'base' => $operation['base'] === null ? null : $operation['base'] + $prefix,
                'head' => $operation['head'] === null ? null : $operation['head'] + $prefix,
            ];
        }

        for ($index = 0; $index < $suffix; $index++) {
            $operations[] = ['base' => $baseCount - $suffix + $index, 'head' => $headCount - $suffix + $index];
        }

        return $operations;
    }

    /**
     * Matches the differing middle of two sequences.
     *
     * @param list<string> $base
     * @param list<string> $head
     *
     * @return list<array{base: ?int, head: ?int}>
     */
    public function middle(array $base, array $head): array
    {
        $baseCount = count($base);
        $headCount = count($head);
        if ($baseCount === 0 || $headCount === 0 || $baseCount * $headCount > self::MAX_MATRIX_CELLS) {
            return $this->replacement($baseCount, $headCount);
        }

        return $this->walk($base, $head, $this->table($base, $head));
    }

    /**
     * Builds the subsequence length table of two sequences.
     *
     * @param list<string> $base
     * @param list<string> $head
     *
     * @return array<int, array<int, int>>
     */
    public function table(array $base, array $head): array
    {
        $baseCount = count($base);
        $headCount = count($head);
        $table = [];
        for ($row = 0; $row <= $baseCount; $row++) {
            $table[$row] = array_fill(0, $headCount + 1, 0);
        }

        for ($row = $baseCount - 1; $row >= 0; $row--) {
            for ($column = $headCount - 1; $column >= 0; $column--) {
                $table[$row][$column] = $base[$row] === $head[$column]
                    ? $table[$row + 1][$column + 1] + 1
                    : max($table[$row + 1][$column], $table[$row][$column + 1]);
            }
        }

        return $table;
    }

    /**
     * Walks the length table into the merged position list.
     *
     * A removal is emitted before an addition at the same place, so a
     * changed element reads as the old value followed by the new one.
     *
     * @param list<string> $base
     * @param list<string> $head
     * @param array<int, array<int, int>> $table
     *
     * @return list<array{base: ?int, head: ?int}>
     */
    public function walk(array $base, array $head, array $table): array
    {
        $operations = [];
        $baseCount = count($base);
        $headCount = count($head);
        $row = 0;
        $column = 0;
        while ($row < $baseCount && $column < $headCount) {
            if ($base[$row] === $head[$column]) {
                $operations[] = ['base' => $row, 'head' => $column];
                $row++;
                $column++;
                continue;
            }

            if (($table[$row + 1][$column] ?? 0) >= ($table[$row][$column + 1] ?? 0)) {
                $operations[] = ['base' => $row, 'head' => null];
                $row++;
                continue;
            }

            $operations[] = ['base' => null, 'head' => $column];
            $column++;
        }

        return $this->tail($operations, $baseCount, $headCount, $row, $column);
    }

    /**
     * Appends the unmatched remainder of both sequences.
     *
     * @param list<array{base: ?int, head: ?int}> $operations
     *
     * @return list<array{base: ?int, head: ?int}>
     */
    public function tail(array $operations, int $baseCount, int $headCount, int $row, int $column): array
    {
        for ($index = $row; $index < $baseCount; $index++) {
            $operations[] = ['base' => $index, 'head' => null];
        }

        for ($index = $column; $index < $headCount; $index++) {
            $operations[] = ['base' => null, 'head' => $index];
        }

        return $operations;
    }

    /**
     * Treats two sequences without a usable match as a full replacement.
     *
     * @return list<array{base: ?int, head: ?int}>
     */
    public function replacement(int $baseCount, int $headCount): array
    {
        return $this->tail([], $baseCount, $headCount, 0, 0);
    }
}
