<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Cli;

use function array_shift;

use Closure;

use function sprintf;

use Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer;
use Toolkit\TreeGuard\Config\ConfigLoader;
use Toolkit\TreeGuard\Reporting\ReporterFactory;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * CLI entry point for TreeGuard.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private TreeGuardOutputWriter $writer;

    /** @readonly */
    private TreeGuardCliArgumentParser $argumentParser;

    /** @readonly */
    private TreeGuardHelpText $helpText;

    /** @readonly */
    private TreeGuardAnalysisRunner $analysisRunner;

    /** @readonly */
    private ConfigLoader $configLoader;

    /** @readonly */
    private TreeGuardAnalyzer $analyzer;

    /** @readonly */
    private ReporterFactory $reporterFactory;

    /**
     * Creates the TreeGuard CLI application for a project working directory.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        ?ConfigLoader $configLoader = null,
        ?TreeGuardAnalyzer $analyzer = null,
        ?ReporterFactory $reporterFactory = null,
        ?Closure $stdout = null,
        ?Closure $stderr = null,
        ?TreeGuardCliArgumentParser $argumentParser = null,
        ?TreeGuardHelpText $helpText = null,
        ?TreeGuardAnalysisRunner $analysisRunner = null,
    ) {
        $this->configLoader = $configLoader ?? new ConfigLoader();
        $this->analyzer = $analyzer ?? new TreeGuardAnalyzer();
        $this->reporterFactory = $reporterFactory ?? new ReporterFactory();
        $this->writer = new TreeGuardOutputWriter($stdout, $stderr);
        $this->argumentParser = $argumentParser ?? new TreeGuardCliArgumentParser();
        $this->helpText = $helpText ?? new TreeGuardHelpText();
        $this->analysisRunner = $analysisRunner ?? new TreeGuardAnalysisRunner(
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
        } catch (TreeGuardException $exception) {
            $this->writer->writeError(sprintf("TreeGuard error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->writer->write($this->helpText->text());

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf("tree-guard %s\n", self::VERSION));

            return 0;
        }

        return $this->analysisRunner->run($arguments['config'], $arguments['reporter']);
    }
}
