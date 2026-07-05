<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Subscriber;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\EventTestIssueFactory;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
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
        /** @readonly */
        private TestIssueCollector $collector,
        /** @readonly */
        private ?EventTestIssueFactory $factory = null,
    ) {
    }

    /**
     * Records a test error in the collector.
     */
    #[Override]
    public function notify(Errored $event): void
    {
        $factory = $this->factory ?? new EventTestIssueFactory();
        $this->collector->record($factory->fromError($event));
    }
}
