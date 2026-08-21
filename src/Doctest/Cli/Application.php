<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Cli;

use function array_shift;

use Closure;
use PhpAiToolkit\Doctest\Config\ConfigLoader;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Execution\SuiteRunner;
use PhpAiToolkit\Doctest\Reporting\ReporterFactory;

use function sprintf;

/**
 * CLI entry point for doctest.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private DoctestOutputWriter $writer;

    /** @readonly */
    private DoctestCliArgumentParser $argumentParser;

    /** @readonly */
    private DoctestHelpText $helpText;

    /** @readonly */
    private DoctestExecutionRunner $executionRunner;

    /** @readonly */
    private ConfigLoader $configLoader;

    /** @readonly */
    private SuiteRunner $suiteRunner;

    /** @readonly */
    private ReporterFactory $reporterFactory;

    /**
     * Creates the doctest CLI application for a project working directory.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        ?ConfigLoader $configLoader = null,
        ?SuiteRunner $suiteRunner = null,
        ?ReporterFactory $reporterFactory = null,
        ?Closure $stdout = null,
        ?Closure $stderr = null,
        ?DoctestCliArgumentParser $argumentParser = null,
        ?DoctestHelpText $helpText = null,
        ?DoctestExecutionRunner $executionRunner = null,
    ) {
        $this->configLoader = $configLoader ?? new ConfigLoader();
        $this->suiteRunner = $suiteRunner ?? new SuiteRunner();
        $this->reporterFactory = $reporterFactory ?? new ReporterFactory();
        $this->writer = new DoctestOutputWriter($stdout, $stderr);
        $this->argumentParser = $argumentParser ?? new DoctestCliArgumentParser();
        $this->helpText = $helpText ?? new DoctestHelpText();
        $this->executionRunner = $executionRunner ?? new DoctestExecutionRunner(
            $this->workingDirectory,
            $this->configLoader,
            $this->suiteRunner,
            $this->reporterFactory,
            $this->writer,
        );
    }

    /**
     * Runs the CLI and returns the process exit code.
     *
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);
        try {
            $arguments = $this->argumentParser->parse($argv);
        } catch (DoctestException $exception) {
            $this->writer->writeError(sprintf("Doctest error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->writer->write($this->helpText->text());

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf("doctest %s\n", self::VERSION));

            return 0;
        }

        if ($arguments['list']) {
            return $this->executionRunner->list($arguments['config'], $arguments['filter']);
        }

        return $this->executionRunner->run($arguments['config'], $arguments['reporter'], $arguments['filter']);
    }
}
