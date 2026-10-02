<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Analysis;

use function array_fill;
use function count;
use function max;

/**
 * Aligns declared and actual heading sequences along their longest common subsequence.
 */
final class HeadingSequenceAligner
{
    /**
     * Returns the alignment steps in document order.
     *
     * Each step is a pair of an expected index and an actual index. A step with
     * both indexes matches two equal headings, a step with only the expected
     * index drops a declared heading, and a step with only the actual index
     * adds a heading.
     *
     * @param list<string> $expected
     * @param list<string> $actual
     * @return list<array{0: ?int, 1: ?int}>
     */
    public function align(array $expected, array $actual): array
    {
        $expectedCount = count($expected);
        $actualCount = count($actual);
        $lengths = array_fill(0, $expectedCount + 1, array_fill(0, $actualCount + 1, 0));
        for ($i = $expectedCount - 1; $i >= 0; $i--) {
            for ($j = $actualCount - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $expected[$i] === $actual[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $steps = [];
        $i = 0;
        $j = 0;
        while ($i < $expectedCount || $j < $actualCount) {
            if ($i < $expectedCount && $j < $actualCount && $expected[$i] === $actual[$j]) {
                $steps[] = [$i++, $j++];
            } elseif ($j >= $actualCount || $i < $expectedCount && $lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $steps[] = [$i++, null];
            } else {
                $steps[] = [null, $j++];
            }
        }

        return $steps;
    }
}
