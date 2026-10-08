<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown\Badge;

use function array_merge;
use function count;

use Guard\Diagnostic\PolicyException;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use Guard\Structure\Markdown\MarkdownLineSplitter;
use Guard\Structure\Source;
use Guard\Structure\Structurer;
use JsonException;

use function preg_match;

use RuntimeException;

use function trim;

/**
 * Locates the badge block that follows a document's level-1 title.
 *
 * The block is the first run of badge-only lines after the title, skipping blank lines. A blank line or any
 * other content ends it. Badge lines above the title are recorded so a misplaced block can be reported.
 */
final class BadgeStructurer implements Structurer
{
    /**
     * Reads the title from the shared "markdown.headings" structure and the badges from the source lines.
     *
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function structure(Source $source): BadgeBlock
    {
        $headings = $source->structure('markdown.headings');
        if (!$headings instanceof HeadingList) {
            throw new PolicyException('BadgeStructurer requires markdown.headings to return HeadingList. Register HeadingStructurer for that structure.');
        }
        $title = null;
        foreach ($headings->all() as $heading) {
            if ($heading->level === 1) {
                $title = $heading;
                break;
            }
        }
        $lines = (new MarkdownLineSplitter())->split($source->text());
        if ($title === null) {
            return new BadgeBlock(null, [], $this->earlyLine($lines, count($lines)));
        }

        return new BadgeBlock($title, $this->badges($lines, $this->bodyStart($lines, $title)), $this->earlyLine($lines, $title->line - 1));
    }

    /**
     * Returns the index of the first line after the title, past a setext underline.
     *
     * @param list<string> $lines
     */
    public function bodyStart(array $lines, Heading $title): int
    {
        $index = $title->line - 1;
        if (preg_match('/^ {0,3}#/', $lines[$index] ?? '') === 1) {
            return $index + 1;
        }
        $count = count($lines);
        for ($next = $index + 1; $next < $count; $next++) {
            if (preg_match('/^ {0,3}=+[ \t]*$/', $lines[$next]) === 1) {
                return $next + 1;
            }
        }

        return $index + 1;
    }

    /**
     * Collects the first run of badge lines, skipping the blank lines before it.
     *
     * @param list<string> $lines
     * @return list<Badge>
     */
    public function badges(array $lines, int $start): array
    {
        $count = count($lines);
        $index = $start;
        while ($index < $count && trim($lines[$index]) === '') {
            $index++;
        }
        $badges = [];
        $parser = new BadgeLineParser();
        for (; $index < $count; $index++) {
            $found = $parser->parse($lines[$index], $index + 1);
            if ($found === null) {
                break;
            }
            $badges = array_merge($badges, $found);
        }

        return $badges;
    }

    /**
     * Returns the 1-based number of the first badge line before the given index, if any.
     *
     * @param list<string> $lines
     */
    public function earlyLine(array $lines, int $end): ?int
    {
        $parser = new BadgeLineParser();
        for ($index = 0; $index < $end; $index++) {
            if ($parser->parse($lines[$index], $index + 1) !== null) {
                return $index + 1;
            }
        }

        return null;
    }
}
