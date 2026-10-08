<?php

declare(strict_types=1);

namespace Guard\Reporting;

use Guard\Policy\FileChange;

/**
 * Builds a unified hunk with three context lines, retaining final-newline changes.
 */
final class ChangeDiff
{
    /**
     * Returns the exact replacement as a unified diff without reading the filesystem.
     */
    public function render(FileChange $change): string
    {
        if ($change->original === $change->replacement) {
            return '';
        }
        $before = $this->lines($change->original);
        $after = $this->lines($change->replacement);
        $prefix = 0;
        $length = min(count($before), count($after));
        while ($prefix < $length && $before[$prefix] === $after[$prefix]) {
            ++$prefix;
        }
        $suffix = 0;
        while ($suffix < $length - $prefix && $before[count($before) - $suffix - 1] === $after[count($after) - $suffix - 1]) {
            ++$suffix;
        }
        $start = max(0, $prefix - 3);
        $tail = min(3, $suffix);
        $oldCount = count($before) - $suffix + $tail - $start;
        $newCount = count($after) - $suffix + $tail - $start;
        $lines = ['--- a/' . $change->path, '+++ b/' . $change->path,
            sprintf('@@ -%d,%d +%d,%d @@', $oldCount === 0 ? 0 : $start + 1, $oldCount, $newCount === 0 ? 0 : $start + 1, $newCount)];
        $this->append($lines, array_slice($before, $start, $prefix - $start), ' ');
        $this->append($lines, array_slice($before, $prefix, count($before) - $prefix - $suffix), '-');
        $this->append($lines, array_slice($after, $prefix, count($after) - $prefix - $suffix), '+');
        $this->append($lines, array_slice($before, count($before) - $suffix, $tail), ' ');
        return implode("\n", $lines) . "\n";
    }

    /**
     * @return list<string>
     */
    public function lines(string $text): array
    {
        if ($text === '') {
            return [];
        }
        return $this->terminatedLines($text);
    }

    /**
     * @return list<string>
     */
    public function terminatedLines(string $text): array
    {
        $lines = explode("\n", $text);
        $last = array_pop($lines);
        $lines = array_map(static fn (string $line): string => $line . "\n", $lines);
        if ($last !== '') {
            $lines[] = $last;
        }
        return $lines;
    }

    /**
     * @param list<string> $result
     * @param list<string> $lines
     */
    public function append(array &$result, array $lines, string $prefix): void
    {
        foreach ($lines as $line) {
            $result[] = $prefix . rtrim($line, "\n");
            if (!str_ends_with($line, "\n")) {
                $result[] = '\\ No newline at end of file';
            }
        }
    }
}
