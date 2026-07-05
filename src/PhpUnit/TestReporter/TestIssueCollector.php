<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function count;

/**
 * Collects test issues (failures, errors, risky) during a PHPUnit run.
 *
 * Converts version-neutral issue inputs into TestIssue value objects and
 * extracts precise locations from stack traces.
 */
final class TestIssueCollector
{
    /** @var list<TestIssue> */
    private array $issues = [];

    /** @readonly */
    private TestFailureLineResolver $failureLineResolver;

    /** @readonly */
    private TestIssueSourceLocationResolver $sourceLocationResolver;

    /**
     * Creates the issue collector from event-to-issue resolver collaborators.
     */
    public function __construct(
        ?TestFailureLineResolver $failureLineResolver = null,
        ?TestIssueSourceLocationResolver $sourceLocationResolver = null,
    ) {
        $this->failureLineResolver = $failureLineResolver ?? new TestFailureLineResolver();
        $this->sourceLocationResolver = $sourceLocationResolver ?? new TestIssueSourceLocationResolver();
    }

    /**
     * Records a normalized test issue.
     */
    public function record(TestIssueInput $input): void
    {
        $testLine = $input->testLine > 0
            ? $this->failureLineResolver->resolve($input->stackTrace, $input->testFile, $input->testLine)
            : 0;
        $sourceLocation = $this->sourceLocationResolver->resolve($input->stackTrace, $input->testFile);

        $this->issues[] = new TestIssue(
            $input->type,
            $input->testId,
            $input->testName,
            $input->testFile,
            $testLine,
            $input->message,
            $input->diff,
            $sourceLocation !== null ? $sourceLocation['file'] : null,
            $sourceLocation !== null ? $sourceLocation['line'] : null,
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
