<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Cli;

use function array_shift;

use Closure;

use function sprintf;

use Toolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer;
use Toolkit\ScopeGuard\Config\ConfigLoader;
use Toolkit\ScopeGuard\Reporting\ReporterFactory;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * CLI entry point for ScopeGuard.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private ScopeGuardOutputWriter $writer;

    /** @readonly */
    private ScopeGuardCliArgumentParser $argumentParser;

    /** @readonly */
    private ScopeGuardHelpText $helpText;

    /** @readonly */
    private ScopeGuardAnalysisRunner $analysisRunner;

    /** @readonly */
    private ConfigLoader $configLoader;

    /** @readonly */
    private ScopeGuardAnalyzer $analyzer;

    /** @readonly */
    private ReporterFactory $reporterFactory;

    /**
     * Creates the ScopeGuard CLI application for a project working directory.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        ?ConfigLoader $configLoader = null,
        ?ScopeGuardAnalyzer $analyzer = null,
        ?ReporterFactory $reporterFactory = null,
        ?Closure $stdout = null,
        ?Closure $stderr = null,
        ?ScopeGuardCliArgumentParser $argumentParser = null,
        ?ScopeGuardHelpText $helpText = null,
        ?ScopeGuardAnalysisRunner $analysisRunner = null,
    ) {
        $this->configLoader = $configLoader ?? new ConfigLoader();
        $this->analyzer = $analyzer ?? new ScopeGuardAnalyzer();
        $this->reporterFactory = $reporterFactory ?? new ReporterFactory();
        $this->writer = new ScopeGuardOutputWriter($stdout, $stderr);
        $this->argumentParser = $argumentParser ?? new ScopeGuardCliArgumentParser();
        $this->helpText = $helpText ?? new ScopeGuardHelpText();
        $this->analysisRunner = $analysisRunner ?? new ScopeGuardAnalysisRunner(
            $this->workingDirectory,
            $this->configLoader,
            $this->analyzer,
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
        } catch (ScopeGuardException $exception) {
            $this->writer->writeError(sprintf("ScopeGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->writer->write($this->helpText->text());

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf("scope-guard %s\n", self::VERSION));

            return 0;
        }

        return $this->analysisRunner->run($arguments['config'], $arguments['reporter']);
    }
}
