<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function preg_match;
use function trim;

/**
 * Parses stack-trace frame text emitted by supported PHPUnit versions.
 */
final class StackTraceFrameLocationParser
{
    /**
     * Extracts a file and line pair from one stack-trace frame.
     *
     * @return array{file: string, line: int}|null
     */
    public function parse(string $frame): ?array
    {
        $frame = trim($frame);
        $matches = [];

        if (preg_match('/^(.+):(\d+)$/', $frame, $matches) === 1) {
            return ['file' => trim($matches[1]), 'line' => (int) $matches[2]];
        }

        if (preg_match('/^#\d+\s+(.+)\((\d+)\):/', $frame, $matches) === 1) {
            return ['file' => trim($matches[1]), 'line' => (int) $matches[2]];
        }

        return null;
    }
}
