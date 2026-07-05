<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use Closure;

use function getenv;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\ExecutionFinishedSubscriber;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestConsideredRiskySubscriber;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestErroredSubscriber;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\TestFailedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * PHPUnit extension that provides dual-mode test result reporting.
 *
 * AI mode — replaces progress/result with structured plain text.
 * Human mode — supplements PHPUnit's default output on STDERR.
 *
 * Not compatible with ParaTest; skips bootstrap when running
 * inside a ParaTest worker process.
 */
final class AiTestReporterExtension implements Extension
{
    /** @var Closure(string): void|null */
    private ?Closure $writer;

    /**
     * @param Closure(string): void|null $writer output writer for testing (defaults to STDERR)
     */
    public function __construct(?Closure $writer = null)
    {
        $this->writer = $writer;
    }

    /**
     * Bootstraps the extension by registering event subscribers.
     *
     * Skips bootstrap when running inside ParaTest (PARATEST env var)
     * since ParaTest manages its own output and does not support
     * PHPUnit extension output replacement.
     */
    #[Override]
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if (getenv('PARATEST') !== false) {
            return;
        }

        $isAiMode = TestReporterRuntime::isAiMode();

        if ($isAiMode) {
            $facade->replaceProgressOutput();
            $facade->replaceResultOutput();
        }

        $runtime = TestReporterRuntime::fromCurrentProcess($this->writer, $isAiMode);

        $facade->registerSubscribers(
            new TestFailedSubscriber($runtime->collector()),
            new TestErroredSubscriber($runtime->collector()),
            new TestConsideredRiskySubscriber($runtime->collector()),
            new ExecutionFinishedSubscriber($runtime),
        );
    }
}
