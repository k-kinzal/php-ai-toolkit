<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use PHPUnit\Event\Code\Throwable;

use function preg_match_all;
use function preg_quote;

/**
 * Resolves the most precise test failure line from a PHPUnit stack trace.
 */
final class TestFailureLineResolver
{
    /**
     * Returns the first stack frame line in the test file, or the fallback line.
     */
    public function resolve(Throwable $throwable, string $testFile, int $fallbackLine): int
    {
        $stackTrace = $throwable->stackTrace();
        if ($stackTrace === '') {
            return $fallbackLine;
        }

        $matches = [];
        $escaped = preg_quote($testFile, '/');
        if (preg_match_all('/^' . $escaped . ':(\d+)$/m', $stackTrace, $matches) > 0) {
            return (int) $matches[1][0];
        }

        return $fallbackLine;
    }
}
