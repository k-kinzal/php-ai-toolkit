<?php

declare(strict_types=1);

namespace PhpStanAiRules\TestReporter\Subscriber;

use Override;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;

/**
 * Forwards test error events to the issue collector.
 */
final class TestErroredSubscriber implements ErroredSubscriber
{
    /**
     * @param TestIssueCollector $collector accumulates test issues
     */
    public function __construct(
        private readonly TestIssueCollector $collector,
    ) {
    }

    /**
     * Records a test error in the collector.
     */
    #[Override]
    public function notify(Errored $event): void
    {
        $this->collector->recordError($event);
    }
}
