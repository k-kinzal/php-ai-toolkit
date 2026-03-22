<?php

declare(strict_types=1);

namespace PhpStanAiRules\TestReporter\Subscriber;

use Override;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;

/**
 * Forwards test failure events to the issue collector.
 */
final class TestFailedSubscriber implements FailedSubscriber
{
    /**
     * @param TestIssueCollector $collector accumulates test issues
     */
    public function __construct(
        private readonly TestIssueCollector $collector,
    ) {
    }

    /**
     * Records a test failure in the collector.
     */
    #[Override]
    public function notify(Failed $event): void
    {
        $this->collector->recordFailure($event);
    }
}
