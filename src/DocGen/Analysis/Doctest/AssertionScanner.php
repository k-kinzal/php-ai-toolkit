<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Doctest;

use function explode;
use function preg_match;
use function trim;

/**
 * Classifies example lines by their doctest assertion markers.
 *
 * The three patterns and their order match the doctest-php assertion
 * parser, so the rendered markers agree with what doctest would execute.
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
     */
    public function scanLine(string $line): AssertionLine
    {
        $trimmed = trim($line);
        if (preg_match('/^(.+?)\s*\/\/\s*=>\s*(.+)$/', $trimmed, $match) === 1) {
            return new AssertionLine($line, trim($match[1]), 'return', trim($match[2]), null);
        }

        if (preg_match('/^(.+?)\s*\/\/\s*Output:\s*(.*)$/', $trimmed, $match) === 1) {
            return new AssertionLine($line, trim($match[1]), 'output', $match[2], null);
        }

        if (preg_match('/^(.+?)\s*\/\/\s*throws\s+(\S+)(?::\s*(.*))?$/', $trimmed, $match) === 1) {
            return new AssertionLine($line, trim($match[1]), 'throws', $match[2], isset($match[3]) ? trim($match[3]) : null);
        }

        return new AssertionLine($line, $trimmed, null, null, null);
    }
}
