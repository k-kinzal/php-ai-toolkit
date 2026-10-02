<?php

declare(strict_types=1);

namespace Toolkit\PhpUnit\TestReporter\Subscriber;

use Override;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\ConsideredRiskySubscriber;
use Toolkit\PhpUnit\TestReporter\EventTestIssueFactory;
use Toolkit\PhpUnit\TestReporter\TestIssueCollector;

/**
 * Forwards risky test events to the issue collector.
 */
final class TestConsideredRiskySubscriber implements ConsideredRiskySubscriber
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
     * Records a risky test in the collector.
     */
    #[Override]
    public function notify(ConsideredRisky $event): void
    {
        $factory = $this->factory ?? new EventTestIssueFactory();
        $this->collector->record($factory->fromRisky($event));
    }
}
