<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;

/**
 * Converts PHPUnit 10+ event objects into version-neutral issue inputs.
 */
final class EventTestIssueFactory
{
    /**
     * Creates the event issue factory from event-specific collaborators.
     */
    public function __construct(
        /** @readonly */
        private ?EventTestNameResolver $nameResolver = null,
    ) {
    }

    /**
     * Converts a failed-test event into normalized issue data.
     */
    public function fromFailure(Failed $event): TestIssueInput
    {
        $test = $event->test();
        $throwable = $event->throwable();
        $diff = null;

        if ($event->hasComparisonFailure()) {
            $diff = $event->comparisonFailure()->diff();
        }

        return new TestIssueInput(
            TestIssue::TYPE_FAILED,
            $test->id(),
            ($this->nameResolver ?? new EventTestNameResolver())->resolve($test),
            $test->file(),
            $test->isTestMethod() ? $test->line() : 0,
            $throwable->message(),
            $diff,
            $throwable->stackTrace(),
        );
    }

    /**
     * Converts an errored-test event into normalized issue data.
     */
    public function fromError(Errored $event): TestIssueInput
    {
        $test = $event->test();
        $throwable = $event->throwable();

        return new TestIssueInput(
            TestIssue::TYPE_ERROR,
            $test->id(),
            ($this->nameResolver ?? new EventTestNameResolver())->resolve($test),
            $test->file(),
            $test->isTestMethod() ? $test->line() : 0,
            $throwable->message(),
            null,
            $throwable->stackTrace(),
        );
    }

    /**
     * Converts a risky-test event into normalized issue data.
     */
    public function fromRisky(ConsideredRisky $event): TestIssueInput
    {
        $test = $event->test();

        return new TestIssueInput(
            TestIssue::TYPE_RISKY,
            $test->id(),
            ($this->nameResolver ?? new EventTestNameResolver())->resolve($test),
            $test->file(),
            $test->isTestMethod() ? $test->line() : 0,
            $event->message(),
        );
    }
}
