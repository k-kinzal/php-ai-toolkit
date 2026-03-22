<?php

declare(strict_types=1);

namespace PhpStanAiRules\TestReporter;

use function count;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;

use function preg_match_all;
use function preg_quote;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Collects test issues (failures, errors, risky) during a PHPUnit run.
 *
 * Converts PHPUnit event objects into TestIssue value objects and
 * extracts source file locations from stack traces.
 */
final class TestIssueCollector
{
    /** @var list<TestIssue> */
    private array $issues = [];

    /**
     * Records a test failure from a Failed event.
     *
     * Extracts the comparison diff when available and parses the
     * stack trace to locate the implicated source file.
     */
    public function recordFailure(Failed $event): void
    {
        $test = $event->test();
        $throwable = $event->throwable();

        $diff = null;
        if ($event->hasComparisonFailure()) {
            $diff = $event->comparisonFailure()->diff();
        }

        $testFile = $test->file();
        $testLine = $test->isTestMethod() ? $this->resolveFailureLine($throwable, $testFile, $test->line()) : 0;

        $sourceLocation = $this->extractSourceLocation($throwable->stackTrace(), $testFile);

        $this->issues[] = new TestIssue(
            TestIssue::TYPE_FAILED,
            $test->id(),
            $this->resolveTestName($test),
            $testFile,
            $testLine,
            $throwable->message(),
            $diff,
            $sourceLocation !== null ? $sourceLocation['file'] : null,
            $sourceLocation !== null ? $sourceLocation['line'] : null,
        );
    }

    /**
     * Records a test error from an Errored event.
     *
     * Parses the stack trace to locate the implicated source file.
     */
    public function recordError(Errored $event): void
    {
        $test = $event->test();
        $throwable = $event->throwable();

        $testFile = $test->file();
        $testLine = $test->isTestMethod() ? $this->resolveFailureLine($throwable, $testFile, $test->line()) : 0;

        $sourceLocation = $this->extractSourceLocation($throwable->stackTrace(), $testFile);

        $this->issues[] = new TestIssue(
            TestIssue::TYPE_ERROR,
            $test->id(),
            $this->resolveTestName($test),
            $testFile,
            $testLine,
            $throwable->message(),
            null,
            $sourceLocation !== null ? $sourceLocation['file'] : null,
            $sourceLocation !== null ? $sourceLocation['line'] : null,
        );
    }

    /**
     * Records a risky test from a ConsideredRisky event.
     */
    public function recordRisky(ConsideredRisky $event): void
    {
        $test = $event->test();
        $testLine = $test->isTestMethod() ? $test->line() : 0;

        $this->issues[] = new TestIssue(
            TestIssue::TYPE_RISKY,
            $test->id(),
            $this->resolveTestName($test),
            $test->file(),
            $testLine,
            $event->message(),
        );
    }

    /**
     * Returns all collected issues.
     *
     * @return list<TestIssue>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Checks whether any issues have been collected.
     */
    public function hasIssues(): bool
    {
        return count($this->issues) > 0;
    }

    /**
     * Resolves a human-readable test name from a Test object.
     *
     * Returns ClassName::methodName for TestMethod instances,
     * or the raw name for other test types.
     *
     * @param \PHPUnit\Event\Code\Test $test the test to resolve a name for
     */
    private function resolveTestName(\PHPUnit\Event\Code\Test $test): string
    {
        if ($test->isTestMethod()) {
            return $test->nameWithClass();
        }

        return $test->name();
    }

    /**
     * Resolves the failure line from the stack trace or falls back to the method definition line.
     *
     * Scans the stack trace for the first frame matching the test file
     * to find the exact assertion line, rather than the method definition line.
     */
    private function resolveFailureLine(Throwable $throwable, string $testFile, int $fallbackLine): int
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

    /**
     * Extracts the source file location from a stack trace string.
     *
     * Scans the stack trace for frames that are NOT the test file and
     * NOT vendor files, returning the first application code frame.
     * This helps the AI agent identify where the actual bug is.
     *
     * @return array{file: string, line: int}|null the source location or null if not found
     */
    private function extractSourceLocation(string $stackTrace, string $testFile): ?array
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
