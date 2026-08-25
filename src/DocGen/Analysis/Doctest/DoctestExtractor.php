<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Doctest;

use function array_pop;
use function array_shift;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_replace;

use const PREG_SET_ORDER;

use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

use function str_starts_with;
use function trim;

/**
 * Extracts executable examples from PHPDoc blocks.
 *
 * The grammar matches k-kinzal/doctest-php exactly: at-example blocks run
 * until the next tag line, and only fences whose info string is exactly
 * "php" are recognized, so every extracted example is runnable by doctest.
 */
final class DoctestExtractor
{
    /**
     * Extracts all examples of one docblock in doctest order.
     *
     * @return list<DocExample>
     */
    public function extract(string $docComment): array
    {
        $cleaned = $this->cleanDocblock($docComment);
        $examples = [];
        $index = 0;
        foreach ($this->tagExamples($cleaned) as $example) {
            $examples[] = new DocExample($example['description'], $example['code'], $example['source'], $index);
            $index++;
        }

        foreach ($this->fenceExamples($cleaned) as $code) {
            $examples[] = new DocExample(null, $code, 'fence', $index);
            $index++;
        }

        return $examples;
    }

    /**
     * Strips the docblock frame and one leading space per line.
     */
    public function cleanDocblock(string $docComment): string
    {
        $body = preg_replace('/^\/\*\*\s*|\s*\*\/$/s', '', $docComment) ?? $docComment;
        $cleaned = [];
        foreach (explode("\n", $body) as $line) {
            $cleaned[] = preg_replace('/^\s*\*\s?/', '', $line) ?? $line;
        }

        return implode("\n", $cleaned);
    }

    /**
     * Extracts the at-example blocks of a cleaned docblock.
     *
     * A tag with code lines below it is a runnable doctest example. A tag
     * whose only content sits on the tag line itself is treated as a
     * display-only inline example, since doctest-php would skip it.
     *
     * @return list<array{description: ?string, code: string, source: string}>
     */
    public function tagExamples(string $cleaned): array
    {
        $parts = preg_split('/(?=@example\b)/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            $parts = [];
        }

        $examples = [];
        foreach ($parts as $part) {
            if (!str_starts_with(trim($part), '@example')) {
                continue;
            }

            $lines = explode("\n", $part);
            $firstLine = array_shift($lines);
            $description = null;
            if (preg_match('/@example\s+(.+)$/', $firstLine, $match) === 1) {
                $description = trim($match[1]);
            }

            $body = [];
            foreach ($lines as $line) {
                if (preg_match('/^@\w+/', trim($line)) === 1) {
                    break;
                }

                if ($body === [] && trim($line) === '') {
                    continue;
                }

                $body[] = $line;
            }

            while ($body !== [] && trim($body[count($body) - 1]) === '') {
                array_pop($body);
            }

            $code = trim(implode("\n", $body));
            if ($code !== '') {
                $examples[] = ['description' => $description, 'code' => $code, 'source' => 'tag'];
            } elseif ($description !== null) {
                $examples[] = ['description' => null, 'code' => $description, 'source' => 'tag-inline'];
            }
        }

        return $examples;
    }

    /**
     * Extracts the php code fences of a cleaned docblock.
     *
     * @return list<string>
     */
    public function fenceExamples(string $cleaned): array
    {
        preg_match_all('/```php\s*\n(.*?)```/s', $cleaned, $matches, PREG_SET_ORDER);
        $examples = [];
        foreach ($matches as $match) {
            $code = trim($match[1]);
            if ($code !== '') {
                $examples[] = $code;
            }
        }

        return $examples;
    }
}
