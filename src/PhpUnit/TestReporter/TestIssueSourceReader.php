<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function array_key_exists;
use function file;
use function is_array;
use function is_file;
use function rtrim;

/**
 * Reads source context lines for test issue output.
 */
final class TestIssueSourceReader
{
    /**
     * Reads a single source line, or null when it cannot be read.
     */
    public function read(string $filePath, int $line): ?string
    {
        if ($line <= 0 || !is_file($filePath)) {
            return null;
        }

        $lines = @file($filePath);
        if (!is_array($lines)) {
            return null;
        }

        $index = $line - 1;
        if (!array_key_exists($index, $lines)) {
            return null;
        }

        return rtrim($lines[$index], "\r\n");
    }
}
