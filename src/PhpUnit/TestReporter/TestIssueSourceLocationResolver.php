<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function preg_match_all;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Finds the first application source frame in a PHPUnit stack trace.
 */
final class TestIssueSourceLocationResolver
{
    /**
     * Extracts a non-test, non-vendor source location from a stack trace.
     *
     * @return array{file: string, line: int}|null
     */
    public function resolve(string $stackTrace, string $testFile): ?array
    {
        if ($stackTrace === '') {
            return null;
        }

        $matches = [];
        if (preg_match_all('/^(.+):(\d+)$/m', $stackTrace, $matches, PREG_SET_ORDER) === 0) {
            return null;
        }

        foreach ($matches as $match) {
            $file = trim($match[1]);
            $line = (int) $match[2];

            if ($file === $testFile) {
                continue;
            }

            if (str_contains($file, '/vendor/')) {
                continue;
            }

            if (str_starts_with($file, 'phar://')) {
                continue;
            }

            return ['file' => $file, 'line' => $line];
        }

        return null;
    }
}
