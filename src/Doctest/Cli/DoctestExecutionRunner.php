<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Cli;

use PhpAiToolkit\Doctest\Config\ConfigLoader;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Execution\SuiteRunner;
use PhpAiToolkit\Doctest\Reporting\ReporterFactory;

use function sprintf;

/**
 * Runs or lists doctest examples from resolved CLI options.
 *
 * @visibility namespace
 */
final class DoctestExecutionRunner
{
    /** @readonly */
    private DoctestConfigPathResolver $pathResolver;

    /** @readonly */
    private DoctestReporterOverride $reporterOverride;

    /**
     * Creates an execution runner from doctest services.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        /** @readonly */
        private ConfigLoader $configLoader,
        /** @readonly */
        private SuiteRunner $suiteRunner,
        /** @readonly */
        private ReporterFactory $reporterFactory,
        /** @readonly */
        private DoctestOutputWriter $writer,
        ?DoctestConfigPathResolver $pathResolver = null,
        ?DoctestReporterOverride $reporterOverride = null,
    ) {
        $this->pathResolver = $pathResolver ?? new DoctestConfigPathResolver();
        $this->reporterOverride = $reporterOverride ?? new DoctestReporterOverride();
    }

    /**
     * Runs the selected examples and writes the configured report.
     */
    public function run(string $configPath, ?string $reporterOverride, ?string $filter): int
    {
        try {
            $config = $this->reporterOverride->apply($this->config($configPath), $reporterOverride);
            $result = $this->suiteRunner->run($config, $filter);
            $reporter = $this->reporterFactory->create($config->report->reporter);
        } catch (DoctestException $exception) {
            $this->writer->writeError(sprintf("Doctest error: %s\n", $exception->getMessage()));

            return 2;
        }

        $this->writer->write($reporter->report($result, $config->report));

        return $result->hasFailures() ? 1 : 0;
    }

    /**
     * Writes the identifier of every selected example without running any of them.
     */
    public function list(string $configPath, ?string $filter): int
    {
        try {
            $examples = $this->suiteRunner->collect($this->config($configPath), $filter);
        } catch (DoctestException $exception) {
            $this->writer->writeError(sprintf("Doctest error: %s\n", $exception->getMessage()));

            return 2;
        }

        foreach ($examples as $example) {
            $this->writer->write(sprintf("%s\t%s:%d\n", $example->id(), $example->target->reportPath(), $example->line));
        }

        return 0;
    }

    /**
     * Loads the configuration named by the CLI.
     *
     * @throws DoctestException when the configuration is missing or invalid
     */
    public function config(string $configPath): \PhpAiToolkit\Doctest\Config\DoctestConfig
    {
        return $this->configLoader->load($this->pathResolver->resolve($this->workingDirectory, $configPath));
    }
}
