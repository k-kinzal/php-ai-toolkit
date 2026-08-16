<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

use function explode;
use function str_replace;

/**
 * Merges two revisions of one text into an annotated line listing.
 *
 * The lines that decide the match and the lines that are displayed are
 * kept apart, so a listing can compare plain source while showing the
 * highlighted form of the very same lines.
 */
final class LineDiffer
{
    /** @readonly */
    private LcsMatcher $matcher;

    /**
     * Creates a line differ from its matcher.
     */
    public function __construct(?LcsMatcher $matcher = null)
    {
        $this->matcher = $matcher ?? new LcsMatcher();
    }

    /**
     * Splits one text into the lines it is compared by.
     *
     * @return list<string>
     */
    public function lines(string $text): array
    {
        return explode("\n", str_replace("\r\n", "\n", $text));
    }

    /**
     * Merges two line sequences into one annotated listing.
     *
     * @param list<string> $base the compared base lines
     * @param list<string> $head the compared head lines
     * @param list<string> $baseText the displayed form of the base lines
     * @param list<string> $headText the displayed form of the head lines
     *
     * @return list<DiffLine>
     */
    public function merge(array $base, array $head, array $baseText, array $headText): array
    {
        $lines = [];
        foreach ($this->matcher->match($base, $head) as $operation) {
            $baseIndex = $operation['base'];
            $headIndex = $operation['head'];
            if ($headIndex === null) {
                $lines[] = new DiffLine(
                    DiffStatus::REMOVED,
                    $baseIndex === null ? '' : ($baseText[$baseIndex] ?? ''),
                    $baseIndex === null ? null : $baseIndex + 1,
                    null,
                );
                continue;
            }

            $lines[] = new DiffLine(
                $baseIndex === null ? DiffStatus::ADDED : DiffStatus::SAME,
                $headText[$headIndex] ?? '',
                $baseIndex === null ? null : $baseIndex + 1,
                $headIndex + 1,
            );
        }

        return $lines;
    }
}
