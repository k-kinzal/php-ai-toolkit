<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function max;
use function str_pad;

use const STR_PAD_LEFT;

use function strlen;

/**
 * Formats line-number gutters for human issue output.
 */
final class TestIssueGutter
{
    /**
     * Calculates the gutter width needed for line number alignment.
     *
     * @param list<TestIssue> $issues issues in the same file
     */
    public function width(array $issues): int
    {
        $maxLine = 1;
        foreach ($issues as $issue) {
            if ($issue->testLine > $maxLine) {
                $maxLine = $issue->testLine;
            }
        }

        return max(3, strlen((string) $maxLine));
    }

    /**
     * Pads one line number for a fixed-width gutter.
     */
    public function line(string $line, int $width): string
    {
        return str_pad($line, $width, ' ', STR_PAD_LEFT);
    }
}
