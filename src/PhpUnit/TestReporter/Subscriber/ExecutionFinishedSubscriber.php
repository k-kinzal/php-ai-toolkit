<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Subscriber;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as ExecutionFinishedSubscriberInterface;

/**
 * Outputs formatted test results when execution finishes.
 *
 * In AI mode (output replaced), writes success confirmation or
 * structured failure details. In Human mode (supplementary),
 * only writes when issues exist.
 */
final class ExecutionFinishedSubscriber implements ExecutionFinishedSubscriberInterface
{
    /**
     * Creates the PHPUnit 10+ execution-finished adapter.
     */
    public function __construct(
        /** @readonly */
        private TestReporterRuntime $runtime,
    ) {
    }

    /**
     * Formats and writes collected issues when tests finish.
     */
    #[Override]
    public function notify(ExecutionFinished $event): void
    {
        $this->runtime->writeReport();
    }
}
