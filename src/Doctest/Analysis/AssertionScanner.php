<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function explode;
use function is_array;
use function ltrim;
use function preg_match;
use function rtrim;
use function str_starts_with;
use function strlen;
use function substr;

use const T_COMMENT;
use const T_OPEN_TAG;

use function token_get_all;
use function trim;

/**
 * Classifies example lines by their doctest assertion markers.
 *
 * A marker is only read from a real trailing comment, found by lexing the line
 * rather than by matching a slash pair, so a "//" that is part of a string
 * literal stays part of the code it belongs to.
 *
 * @visibility public
 *
 * @example Classifying the lines of an example
 *     $lines = (new AssertionScanner())->scan('$sum = add(1, 2);' . "\n" . '$sum // => 3');
 *     count($lines) // => 2
 *     $lines[1]->marker // => 'return'
 */
final class AssertionScanner
{
    /**
     * Scans example code into classified lines.
     *
     * @return list<AssertionLine>
     */
    public function scan(string $code): array
    {
        $lines = [];
        foreach (explode("\n", $code) as $line) {
            $lines[] = $this->scanLine($line);
        }

        return $lines;
    }

    /**
     * Classifies one example line.
     *
     * @example Reading a documented return value
     *     $line = (new AssertionScanner())->scanLine('add(1, 2) // => 3');
     *     $line->marker // => 'return'
     *     $line->expected // => '3'
     *
     * @example Leaving a slash pair inside a string alone
     *     $line = (new AssertionScanner())->scanLine('$url = "https://example.com";');
     *     $line->marker // => null
     *
     * @example Treating a marker with nothing in front of it as code
     *     $line = (new AssertionScanner())->scanLine('// => 3');
     *     $line->marker // => null
     */
    public function scanLine(string $line): AssertionLine
    {
        $comment = $this->trailingComment($line);
        if ($comment === null) {
            return new AssertionLine($line, trim($line), null, null, null);
        }

        $code = trim(substr($line, 0, $comment['offset']));
        $text = $comment['text'];
        if ($code === '') {
            return new AssertionLine($line, trim($line), null, null, null);
        }

        if (preg_match('/^\/\/\s*=>\s*(.+)$/', $text, $match) === 1) {
            return new AssertionLine($line, $code, 'return', trim($match[1]), null);
        }

        if (preg_match('/^\/\/\s*Output:\s*(.*)$/', $text, $match) === 1) {
            return new AssertionLine($line, $code, 'output', $match[1], null);
        }

        if (preg_match('/^\/\/\s*throws\s+(\S+?)(?::\s*(.*))?$/', $text, $match) === 1) {
            return new AssertionLine($line, $code, 'throws', $match[1], isset($match[2]) ? trim($match[2]) : null);
        }

        return new AssertionLine($line, trim($line), null, null, null);
    }

    /**
     * Returns the last double-slash comment of the line and where it starts.
     *
     * @return array{text: string, offset: int}|null
     */
    public function trailingComment(string $line): ?array
    {
        $offset = 0;
        $prefix = 0;
        $found = null;
        foreach (token_get_all('<?php ' . $line) as $token) {
            $text = is_array($token) ? $token[1] : $token;
            if (is_array($token) && $token[0] === T_OPEN_TAG) {
                $prefix = strlen($text);
            }

            if (is_array($token) && $token[0] === T_COMMENT && str_starts_with(ltrim($text), '//')) {
                $found = ['text' => rtrim(ltrim($text)), 'offset' => $offset - $prefix];
            }

            $offset += strlen($text);
        }

        return $found;
    }
}
