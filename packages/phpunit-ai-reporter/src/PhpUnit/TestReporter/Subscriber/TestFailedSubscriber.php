<?php

declare(strict_types=1);

namespace Toolkit\PhpUnit\TestReporter\Subscriber;

use Override;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use Toolkit\PhpUnit\TestReporter\EventTestIssueFactory;
use Toolkit\PhpUnit\TestReporter\TestIssueCollector;

/**
 * Forwards test failure events to the issue collector.
 */
final class TestFailedSubscriber implements FailedSubscriber
{
    /**
     * @param TestIssueCollector $collector accumulates test issues
     */
    public function __construct(
        /** @readonly */
        private TestIssueCollector $collector,
        /** @readonly */
        private ?EventTestIssueFactory $factory = null,
    ) {
    }

    /**
     * Records a test failure in the collector.
     */
    #[Override]
    public function notify(Failed $event): void
    {
        $factory = $this->factory ?? new EventTestIssueFactory();
        $this->collector->record($factory->fromFailure($event));
    }
}
