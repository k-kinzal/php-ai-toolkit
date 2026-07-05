<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

use function array_shift;

use Closure;
use PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer;
use PhpAiToolkit\LocGuard\Config\ConfigLoader;
use PhpAiToolkit\LocGuard\LocGuardException;
use PhpAiToolkit\LocGuard\Reporting\ReporterFactory;

use function sprintf;

/**
 * CLI entry point for LocGuard.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private LocGuardOutputWriter $writer;

    /** @readonly */
    private LocGuardCliArgumentParser $argumentParser;

    /** @readonly */
    private LocGuardHelpText $helpText;

    /** @readonly */
    private LocGuardAnalysisRunner $analysisRunner;

    /** @readonly */
    private ConfigLoader $configLoader;

    /** @readonly */
    private LocGuardAnalyzer $analyzer;

    /** @readonly */
    private ReporterFactory $reporterFactory;

    /**
     * Creates the LocGuard CLI application for a project working directory.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        ?ConfigLoader $configLoader = null,
        ?LocGuardAnalyzer $analyzer = null,
        ?ReporterFactory $reporterFactory = null,
        ?Closure $stdout = null,
        ?Closure $stderr = null,
        ?LocGuardCliArgumentParser $argumentParser = null,
        ?LocGuardHelpText $helpText = null,
        ?LocGuardAnalysisRunner $analysisRunner = null,
    ) {
        $this->configLoader = $configLoader ?? new ConfigLoader();
        $this->analyzer = $analyzer ?? new LocGuardAnalyzer();
        $this->reporterFactory = $reporterFactory ?? new ReporterFactory();
        $this->writer = new LocGuardOutputWriter($stdout, $stderr);
        $this->argumentParser = $argumentParser ?? new LocGuardCliArgumentParser();
        $this->helpText = $helpText ?? new LocGuardHelpText();
        $this->analysisRunner = $analysisRunner ?? new LocGuardAnalysisRunner(
            $this->workingDirectory,
            $this->configLoader,
            $this->analyzer,
            $this->reporterFactory,
            $this->writer,
        );
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);
        try {
            $arguments = $this->argumentParser->parse($argv);
        } catch (LocGuardException $exception) {
            $this->writer->writeError(sprintf("LocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->writer->write($this->helpText->text());

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf("loc-guard %s\n", self::VERSION));

            return 0;
        }

        return $this->analysisRunner->run($arguments['config'], $arguments['reporter']);
    }
}
