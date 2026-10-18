<?php

declare(strict_types=1);

namespace DecisionsLint\Cli;

use DecisionsLint\Config\Configuration;
use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Http\CurlTransport;
use DecisionsLint\Http\Transport;
use DecisionsLint\Lint\Linter;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Provider\ProviderFactory;
use DecisionsLint\Reporting\ReporterFactory;
use JsonException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Loads the configuration, plans the questions, asks the provider and writes the report.
 */
final class CheckRun
{
    /**
     * Creates a run that reaches the provider over a transport, or over cURL when none is given.
     */
    public function __construct(private ?Transport $transport = null)
    {
    }

    /**
     * Runs a check and returns 1 when a finding reaches the failing severity, 0 otherwise.
     *
     * @param list<string> $paths paths to lint instead of the configured ones, or empty
     * @param list<string> $ruleIds rule ids to run instead of every enabled rule, or empty
     * @param ?string $failOn the severity that fails the run, or null for the configured one
     * @throws LintException when the configuration, an input or the provider is invalid
     * @throws JsonException when the report cannot be encoded
     */
    public function run(string $configPath, string $format, array $paths, array $ruleIds, ?string $failOn, OutputInterface $output): int
    {
        $configuration = (new ConfigurationLoader())->load($configPath);
        $failOn = $this->failOn($failOn ?? $configuration->failOn);
        $plan = (new Planner())->plan($configuration, $paths, $ruleIds);
        $decision = (new ProviderFactory())->create($configuration->provider, $this->transport($configuration));
        $report = (new Linter())->lint($plan, $decision, $failOn);
        $output->write((new ReporterFactory())->create($format)->render($report, $output->isDecorated()), false, OutputInterface::OUTPUT_RAW);

        return $report->failed() ? 1 : 0;
    }

    /**
     * Returns the transport to use: the injected one, or cURL with the configured timeout and concurrency.
     */
    public function transport(Configuration $configuration): Transport
    {
        return $this->transport ?? new CurlTransport($configuration->provider->concurrency, $configuration->provider->timeout);
    }

    /**
     * Returns a failing severity, rejecting one that is not a severity or never.
     *
     * @throws LintException when the value is unknown
     */
    public function failOn(string $failOn): string
    {
        if ($failOn !== 'never' && !in_array($failOn, Severity::ALL, true)) {
            throw new LintException(sprintf('Unsupported --fail-on "%s". Use --fail-on=%s or never.', $failOn, implode(', ', Severity::ALL)));
        }

        return $failOn;
    }
}
