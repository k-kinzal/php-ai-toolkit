<?php

declare(strict_types=1);

namespace PhpStanAiRules\TestReporter;

use Closure;

use function fwrite;
use function getcwd;
use function getenv;

use Override;
use PhpStanAiRules\Support\AgentDetector;
use PhpStanAiRules\Support\FormatMode;
use PhpStanAiRules\TestReporter\Subscriber\ExecutionFinishedSubscriber;
use PhpStanAiRules\TestReporter\Subscriber\TestConsideredRiskySubscriber;
use PhpStanAiRules\TestReporter\Subscriber\TestErroredSubscriber;
use PhpStanAiRules\TestReporter\Subscriber\TestFailedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

use const STDERR;

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
    /** @var Closure(string): void */
    private Closure $writer;

    /**
     * @param Closure(string): void|null $writer output writer for testing (defaults to STDERR)
     */
    public function __construct(?Closure $writer = null)
    {
        $this->writer = $writer ?? static function (string $output): void {
            fwrite(STDERR, $output);
        };
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

        $agentDetector = new AgentDetector();
        $basePath = (string) getcwd();
        $collector = new TestIssueCollector();
        $formatter = new TestIssueFormatter($agentDetector, $basePath);
        $isAiMode = $agentDetector->resolveMode() === FormatMode::AI;

        if ($isAiMode) {
            $facade->replaceProgressOutput();
            $facade->replaceResultOutput();
        }

        $facade->registerSubscribers(
            new TestFailedSubscriber($collector),
            new TestErroredSubscriber($collector),
            new TestConsideredRiskySubscriber($collector),
            new ExecutionFinishedSubscriber($collector, $formatter, $this->writer, $isAiMode),
        );
    }
}
