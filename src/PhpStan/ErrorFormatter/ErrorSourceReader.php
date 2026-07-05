<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function array_key_exists;
use function file;
use function is_array;
use function is_file;
use function rtrim;

/**
 * Reads source lines for PHPStan error context.
 */
final class ErrorSourceReader
{
    /**
     * Reads one source line, or null when the line is unavailable.
     */
    public function read(string $filePath, int $line): ?string
    {
        if (!is_file($filePath)) {
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
