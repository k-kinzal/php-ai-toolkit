<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Parser;

use function array_pop;
use function array_shift;
use function end;
use function explode;

use Generator;

use function implode;

use PhpAiToolkit\Doctest\Scanner\Target;

use function preg_match;
use function preg_match_all;

use const PREG_OFFSET_CAPTURE;

use function preg_replace;

use const PREG_SET_ORDER;

use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

use function str_starts_with;
use function strpos;
use function substr;
use function substr_count;
use function trim;

/**
 * Extracts Example objects from Target docblocks.
 *
 * Supports both at-example tags followed by indented code,
 * and triple-backtick php code fences in markdown style.
 */
final class ExampleExtractor
{
    /**
     * Extracts all examples from a target's docblock.
     *
     * At-example blocks are yielded before code fences, and the index runs
     * across both, which is the order k-kinzal/doctest-php established.
     *
     * @param Target $target the target containing the docblock
     * @return Generator<int, Example> examples found in the docblock
     */
    public function extract(Target $target): Generator
    {
        $docblock = $this->cleanDocblock($target->docblock);
        $index = 0;

        yield from $this->extractExampleTags($docblock, $target, $index);
        yield from $this->extractCodeFences($docblock, $target, $index);
    }

    /**
     * Strips the docblock frame and one leading space per line.
     */
    public function cleanDocblock(string $docblock): string
    {
        $body = preg_replace('/^\/\*\*\s*|\s*\*\/$/s', '', $docblock) ?? $docblock;
        $cleaned = [];
        foreach (explode("\n", $body) as $line) {
            $cleaned[] = preg_replace('/^\s*\*\s?/', '', $line) ?? $line;
        }

        return implode("\n", $cleaned);
    }

    /**
     * Yields the examples introduced by at-example tags.
     *
     * @param int $index running example index, advanced for each example yielded
     * @return Generator<int, Example>
     */
    public function extractExampleTags(string $docblock, Target $target, int &$index): Generator
    {
        $parts = preg_split('/(?=@example\b)/', $docblock, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return;
        }

        foreach ($parts as $part) {
            if (!str_starts_with(trim($part), '@example')) {
                continue;
            }

            $code = $this->tagCode($part);
            if ($code === '') {
                continue;
            }

            $offset = strpos($docblock, $part);

            yield new Example(
                $code,
                $target,
                $this->calculateLineNumber($docblock, $offset === false ? 0 : $offset, $target->line),
                $index++,
                $this->tagDescription($part),
            );
        }
    }

    /**
     * Returns the description written on an at-example tag line, if any.
     */
    public function tagDescription(string $part): ?string
    {
        $lines = explode("\n", $part);
        $firstLine = array_shift($lines);
        $match = [];
        if (preg_match('/@example\s+(.+)$/', $firstLine, $match) === 1) {
            $description = trim($match[1]);

            return $description === '' ? null : $description;
        }

        return null;
    }

    /**
     * Returns the code lines below an at-example tag, up to the next tag.
     */
    public function tagCode(string $part): string
    {
        $lines = explode("\n", $part);
        array_shift($lines);

        $codeLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (preg_match('/^@\w+/', $trimmedLine) === 1) {
                break;
            }

            if ($codeLines === [] && $trimmedLine === '') {
                continue;
            }

            $codeLines[] = $line;
        }

        while ($codeLines !== [] && trim(end($codeLines)) === '') {
            array_pop($codeLines);
        }

        return trim(implode("\n", $codeLines));
    }

    /**
     * Yields the examples written as triple-backtick php code fences.
     *
     * @param int $index running example index, advanced for each example yielded
     * @return Generator<int, Example>
     */
    public function extractCodeFences(string $docblock, Target $target, int &$index): Generator
    {
        $matches = [];
        if (preg_match_all('/```php\s*\n(.*?)```/s', $docblock, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return;
        }

        foreach ($matches as $match) {
            $code = trim($match[1][0]);
            if ($code === '') {
                continue;
            }

            yield new Example($code, $target, $this->calculateLineNumber($docblock, $match[0][1], $target->line), $index++);
        }
    }

    /**
     * Returns the source line an example body starts on.
     */
    public function calculateLineNumber(string $docblock, int $offset, int $baseLine): int
    {
        $beforeMatch = substr($docblock, 0, $offset);
        $linesBeforeMatch = substr_count($beforeMatch, "\n");

        return $baseLine + $linesBeforeMatch + 1;
    }
}
