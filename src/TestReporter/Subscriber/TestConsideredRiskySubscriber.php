<?php

declare(strict_types=1);

namespace PhpStanAiRules\TestReporter\Subscriber;

use Override;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\ConsideredRiskySubscriber;

/**
 * Forwards risky test events to the issue collector.
 */
final class TestConsideredRiskySubscriber implements ConsideredRiskySubscriber
{
    /**
     * @param TestIssueCollector $collector accumulates test issues
     */
    public function __construct(
        private readonly TestIssueCollector $collector,
    ) {
    }

    /**
     * Records a risky test in the collector.
     */
    #[Override]
    public function notify(ConsideredRisky $event): void
    {
        $this->collector->recordRisky($event);
    }
}
