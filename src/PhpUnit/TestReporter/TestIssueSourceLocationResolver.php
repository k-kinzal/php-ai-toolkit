<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function explode;
use function str_contains;
use function str_starts_with;

/**
 * Finds the first application source frame in a PHPUnit stack trace.
 */
final class TestIssueSourceLocationResolver
{
    /**
     * Creates the source location resolver from a stack frame parser.
     */
    public function __construct(
        /** @readonly */
        private ?StackTraceFrameLocationParser $frameParser = null,
    ) {
    }

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

        foreach (explode("\n", $stackTrace) as $frame) {
            $parser = $this->frameParser ?? new StackTraceFrameLocationParser();
            $location = $parser->parse($frame);
            if ($location === null) {
                continue;
            }

            if ($location['file'] === $testFile) {
                continue;
            }

            if (str_contains($location['file'], '/vendor/')) {
                continue;
            }

            if (str_starts_with($location['file'], 'phar://')) {
                continue;
            }

            return $location;
        }

        return null;
    }
}
