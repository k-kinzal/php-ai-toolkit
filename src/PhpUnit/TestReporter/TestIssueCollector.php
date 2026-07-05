<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function count;

use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;

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

    private readonly TestIssueNameResolver $nameResolver;

    private readonly TestFailureLineResolver $failureLineResolver;

    private readonly TestIssueSourceLocationResolver $sourceLocationResolver;

    /**
     * Creates the issue collector from event-to-issue resolver collaborators.
     */
    public function __construct(
        ?TestIssueNameResolver $nameResolver = null,
        ?TestFailureLineResolver $failureLineResolver = null,
        ?TestIssueSourceLocationResolver $sourceLocationResolver = null,
    ) {
        $this->nameResolver = $nameResolver ?? new TestIssueNameResolver();
        $this->failureLineResolver = $failureLineResolver ?? new TestFailureLineResolver();
        $this->sourceLocationResolver = $sourceLocationResolver ?? new TestIssueSourceLocationResolver();
    }

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
        $testLine = $test->isTestMethod() ? $this->failureLineResolver->resolve($throwable, $testFile, $test->line()) : 0;

        $sourceLocation = $this->sourceLocationResolver->resolve($throwable->stackTrace(), $testFile);

        $this->issues[] = new TestIssue(
            TestIssue::TYPE_FAILED,
            $test->id(),
            $this->nameResolver->resolve($test),
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
        $testLine = $test->isTestMethod() ? $this->failureLineResolver->resolve($throwable, $testFile, $test->line()) : 0;

        $sourceLocation = $this->sourceLocationResolver->resolve($throwable->stackTrace(), $testFile);

        $this->issues[] = new TestIssue(
            TestIssue::TYPE_ERROR,
            $test->id(),
            $this->nameResolver->resolve($test),
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
            $this->nameResolver->resolve($test),
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
}
