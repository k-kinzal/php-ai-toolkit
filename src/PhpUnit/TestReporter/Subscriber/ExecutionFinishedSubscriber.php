<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Subscriber;

use Closure;
use Override;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
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
     * @param TestIssueCollector $collector the collected test issues
     * @param TestIssueFormatter $formatter the dual-mode formatter
     * @param Closure(string): void $writer output writer
     * @param bool $replacedOutput whether PHPUnit's default output was replaced
     */
    public function __construct(
        private readonly TestIssueCollector $collector,
        private readonly TestIssueFormatter $formatter,
        private readonly Closure $writer,
        private readonly bool $replacedOutput = false,
    ) {
    }

    /**
     * Formats and writes collected issues when tests finish.
     */
    #[Override]
    public function notify(ExecutionFinished $event): void
    {
        if (!$this->collector->hasIssues()) {
            if ($this->replacedOutput) {
                ($this->writer)("No test failures\n");
            }

            return;
        }

        $output = $this->formatter->format($this->collector->getIssues());
        ($this->writer)($output);
    }
}
